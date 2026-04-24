using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using SignUpLogin.Data;
using SignUpLogin.Models;
using SignUpLogin.Models.ViewModels;

namespace SignUpLogin.Controllers
{
    public class AdminController : Controller
    {
        private readonly ApplicationDbContext _context;
        private const int DefaultSessions = 30;
        private static readonly string[] LabNames = { "Lab 524", "Lab 526", "Lab 528", "Lab 542", "Lab 544" };

        public AdminController(ApplicationDbContext context)
        {
            _context = context;
        }

        // ── Dashboard ──────────────────────────────────────────────────────
        public async Task<IActionResult> Home()
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var currentSitIns = await _context.SitInRecords
                .Include(r => r.Student)
                .AsNoTracking()
                .Where(r => r.TimeOut == null)
                .OrderByDescending(r => r.TimeIn)
                .Take(10)
                .ToListAsync();

            var announcements = await _context.Announcements
                .AsNoTracking()
                .OrderByDescending(a => a.PostedAt)
                .Take(5)
                .ToListAsync();

            // Ensure every lab has a row; create missing ones as "Available"
            var labStatuses = await _context.LabStatuses.ToListAsync();
            bool anyAdded = false;
            foreach (var name in LabNames)
            {
                if (!labStatuses.Any(l => l.LabName == name))
                {
                    var newLab = new LabStatus { LabName = name, Status = "Available", UpdatedAt = DateTime.UtcNow };
                    _context.LabStatuses.Add(newLab);
                    labStatuses.Add(newLab);
                    anyAdded = true;
                }
            }
            if (anyAdded) await _context.SaveChangesAsync();

            var orderedLabStatuses = LabNames
                .Select(n => labStatuses.First(l => l.LabName == n))
                .ToList();

            var vm = new AdminDashboardViewModel
            {
                UserName = HttpContext.Session.GetString("UserName") ?? "Admin",
                StudentsRegistered = await _context.Signups.CountAsync(s => s.Role == "Student"),
                CurrentlySitIn = currentSitIns.Count,
                TotalSitInRecords = await _context.SitInRecords.CountAsync(),
                CurrentSitIns = currentSitIns,
                Announcements = announcements,
                LabStatuses = orderedLabStatuses
            };

            return View(vm);
        }

        // ── Lab Status ─────────────────────────────────────────────────────
        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> UpdateLabStatus(string LabName, string Status, string? Remarks)
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var lab = await _context.LabStatuses.FirstOrDefaultAsync(l => l.LabName == LabName);
            if (lab == null)
            {
                lab = new LabStatus { LabName = LabName };
                _context.LabStatuses.Add(lab);
            }

            lab.Status = Status?.Trim() ?? "Available";
            lab.Remarks = string.IsNullOrWhiteSpace(Remarks) ? null : Remarks.Trim();
            lab.UpdatedAt = DateTime.UtcNow;

            await _context.SaveChangesAsync();

            TempData["Success"] = $"{LabName} status updated to \"{lab.Status}\".";
            return RedirectToAction(nameof(Home));
        }

        // ── Students ───────────────────────────────────────────────────────
        public async Task<IActionResult> Students()
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var students = await _context.Signups
                .AsNoTracking()
                .Where(s => s.Role == "Student")
                .OrderBy(s => s.LastName)
                .ToListAsync();

            return View(students);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> EditStudent(string IdNumber, string FirstName, string LastName,
                                                      string CourseLevel, string Course, int RemainingSessions)
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var student = await _context.Signups
                .FirstOrDefaultAsync(s => s.IdNumber == IdNumber && s.Role == "Student");

            if (student == null)
            {
                TempData["Error"] = "Student not found.";
                return RedirectToAction(nameof(Students));
            }

            student.FirstName = FirstName?.Trim() ?? student.FirstName;
            student.LastName = LastName?.Trim() ?? student.LastName;
            student.CourseLevel = CourseLevel?.Trim() ?? student.CourseLevel;
            student.Course = Course?.Trim() ?? student.Course;
            student.RemainingSessions = RemainingSessions;

            await _context.SaveChangesAsync();

            TempData["Success"] = $"Student {FirstName} {LastName} updated successfully.";
            return RedirectToAction(nameof(Students));
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> DeleteStudent(string idNumber)
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var student = await _context.Signups
                .Include(s => s.SitInRecords)
                .Include(s => s.Feedbacks)
                .Include(s => s.StudentPoints)
                .FirstOrDefaultAsync(s => s.IdNumber == idNumber && s.Role == "Student");

            if (student == null)
            {
                TempData["Error"] = "Student not found.";
                return RedirectToAction(nameof(Students));
            }

            _context.SitInRecords.RemoveRange(student.SitInRecords);
            _context.Feedbacks.RemoveRange(student.Feedbacks);
            if (student.StudentPoints != null) _context.StudentPoints.Remove(student.StudentPoints);

            _context.Signups.Remove(student);
            await _context.SaveChangesAsync();

            TempData["Success"] = $"Student {student.FirstName} {student.LastName} deleted.";
            return RedirectToAction(nameof(Students));
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> ResetAllSessions()
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            await _context.Signups
                .Where(s => s.Role == "Student")
                .ExecuteUpdateAsync(s => s.SetProperty(p => p.RemainingSessions, DefaultSessions));

            TempData["Success"] = $"All student sessions reset to {DefaultSessions}.";
            return RedirectToAction(nameof(Students));
        }

        // ── Sit-in ─────────────────────────────────────────────────────────
        [HttpGet]
        public async Task<IActionResult> LookupStudent(string idNumber)
        {
            if (!IsAdmin()) return Unauthorized();

            if (string.IsNullOrWhiteSpace(idNumber))
                return Json(new { found = false });

            var student = await _context.Signups
                .AsNoTracking()
                .FirstOrDefaultAsync(s => s.IdNumber == idNumber && s.Role == "Student");

            if (student == null) return Json(new { found = false });

            return Json(new
            {
                found = true,
                idNumber = student.IdNumber,
                name = $"{student.FirstName} {student.LastName}".Trim(),
                remainingSessions = student.RemainingSessions
            });
        }

        [HttpGet]
        public async Task<IActionResult> GetOccupiedPcs(string laboratory)
        {
            if (!IsAdmin()) return Unauthorized();

            if (string.IsNullOrWhiteSpace(laboratory))
                return Json(new { occupiedPcs = Array.Empty<string>() });

            var occupiedPcs = await _context.SitInRecords
                .AsNoTracking()
                .Where(r => r.Laboratory == laboratory && r.TimeOut == null && r.PcNumber != null)
                .Select(r => r.PcNumber)
                .ToListAsync();

            return Json(new { occupiedPcs });
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> SitIn(string StudentIdNumber, string Purpose, string Laboratory, string? PcNumber)
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            if (string.IsNullOrWhiteSpace(StudentIdNumber) ||
                string.IsNullOrWhiteSpace(Purpose) ||
                string.IsNullOrWhiteSpace(Laboratory))
            {
                TempData["Error"] = "All fields are required.";
                return RedirectToAction(nameof(Home));
            }

            var student = await _context.Signups
                .FirstOrDefaultAsync(s => s.IdNumber == StudentIdNumber && s.Role == "Student");

            if (student == null)
            {
                TempData["Error"] = "Student not found.";
                return RedirectToAction(nameof(Home));
            }

            var existing = await _context.SitInRecords
                .AnyAsync(r => r.StudentIdNumber == StudentIdNumber && r.TimeOut == null);

            if (existing)
            {
                TempData["Error"] = "Student already has an active sit-in session.";
                return RedirectToAction(nameof(Home));
            }

            var record = new SitInRecord
            {
                StudentIdNumber = StudentIdNumber,
                Purpose = Purpose,
                Laboratory = Laboratory,
                PcNumber = PcNumber,
                TimeIn = DateTime.UtcNow
            };

            _context.SitInRecords.Add(record);

            if (student.RemainingSessions > 0)
                student.RemainingSessions--;

            await _context.SaveChangesAsync();

            TempData["Success"] = $"Sit-in recorded for {student.FirstName} {student.LastName}.";
            return RedirectToAction(nameof(Home));
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> EndSitIn(int sitInId)
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var record = await _context.SitInRecords
                .FirstOrDefaultAsync(r => r.Id == sitInId && r.TimeOut == null);

            if (record == null)
            {
                TempData["Error"] = "Active sit-in session not found.";
                return RedirectToAction(nameof(Home));
            }

            record.TimeOut = DateTime.UtcNow;
            await _context.SaveChangesAsync();

            TempData["Success"] = "Sit-in session ended successfully.";
            return RedirectToAction(nameof(Home));
        }

        // ── Sit-in Records ─────────────────────────────────────────────────
        public async Task<IActionResult> SitInRecords()
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var records = await _context.SitInRecords
                .AsNoTracking()
                .Include(r => r.Student)
                .OrderByDescending(r => r.TimeIn)
                .ToListAsync();

            return View(records);
        }

        // ── Announcements ──────────────────────────────────────────────────
        public async Task<IActionResult> Announcements()
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var announcements = await _context.Announcements
                .AsNoTracking()
                .OrderByDescending(a => a.PostedAt)
                .ToListAsync();

            return View(announcements);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> PostAnnouncement(string Title, string Message, string PostedBy)
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            if (string.IsNullOrWhiteSpace(Title) || string.IsNullOrWhiteSpace(Message))
            {
                TempData["Error"] = "Title and message are required.";
                return RedirectToAction(nameof(Announcements));
            }

            var announcement = new Announcement
            {
                Title = Title.Trim(),
                Message = Message.Trim(),
                PostedBy = string.IsNullOrWhiteSpace(PostedBy) ? "CCS Admin" : PostedBy.Trim(),
                PostedAt = DateTime.UtcNow
            };

            _context.Announcements.Add(announcement);
            await _context.SaveChangesAsync();

            TempData["Success"] = "Announcement posted successfully.";
            return RedirectToAction(nameof(Announcements));
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> DeleteAnnouncement(int id)
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var announcement = await _context.Announcements.FindAsync(id);
            if (announcement == null)
            {
                TempData["Error"] = "Announcement not found.";
                return RedirectToAction(nameof(Announcements));
            }

            _context.Announcements.Remove(announcement);
            await _context.SaveChangesAsync();

            TempData["Success"] = "Announcement deleted.";
            return RedirectToAction(nameof(Announcements));
        }

        // ── Feedback ───────────────────────────────────────────────────────
        public async Task<IActionResult> Feedback()
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var feedbacks = await _context.Feedbacks
                .AsNoTracking()
                .Include(f => f.Student)
                .Include(f => f.SitInRecord)
                .OrderByDescending(f => f.SubmittedAt)
                .ToListAsync();

            return View(feedbacks);
        }

        // ── Analytics ──────────────────────────────────────────────────────
        public async Task<IActionResult> Analytics()
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var totalSessions = await _context.SitInRecords.CountAsync();
            var activeSessions = await _context.SitInRecords.CountAsync(r => r.TimeOut == null);
            var avgRating = await _context.Feedbacks.AnyAsync()
                                ? await _context.Feedbacks.AverageAsync(f => (double)f.Rating) : 0;
            var totalFeedbacks = await _context.Feedbacks.CountAsync();

            var sessionsByLab = await _context.SitInRecords
                .GroupBy(r => r.Laboratory)
                .Select(g => new { Lab = g.Key, Count = g.Count() })
                .OrderByDescending(g => g.Count)
                .ToListAsync();

            var topStudents = await _context.StudentPoints
                .AsNoTracking()
                .Include(p => p.Student)
                .OrderByDescending(p => p.Points)
                .Take(10)
                .ToListAsync();

            ViewBag.TotalSessions = totalSessions;
            ViewBag.ActiveSessions = activeSessions;
            ViewBag.AvgRating = avgRating.ToString("0.0");
            ViewBag.TotalFeedbacks = totalFeedbacks;
            ViewBag.SessionsByLab = sessionsByLab;
            ViewBag.TopStudents = topStudents;

            return View();
        }

        // ── ADD POINTS (NOW DASHBOARD-READY) ───────────────────────────────
        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> AddPoints(string StudentIdNumber, int Points, string? Reason)
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var student = await _context.Signups
                .FirstOrDefaultAsync(s => s.IdNumber == StudentIdNumber && s.Role == "Student");

            if (student == null)
            {
                TempData["Error"] = "Student not found.";
                return RedirectToAction(nameof(Home));
            }

            var row = await _context.StudentPoints
                .FirstOrDefaultAsync(p => p.StudentIdNumber == StudentIdNumber);

            if (row == null)
            {
                row = new StudentPoints { StudentIdNumber = StudentIdNumber };
                _context.StudentPoints.Add(row);
            }

            row.Points += Points;
            row.LastRewardReason = string.IsNullOrWhiteSpace(Reason) ? row.LastRewardReason : Reason.Trim();
            row.UpdatedAt = DateTime.UtcNow;

            await _context.SaveChangesAsync();

            TempData["Success"] = $"Added {Points} point(s) to {student.FirstName} {student.LastName}. New total: {row.Points}.";
            return RedirectToAction(nameof(Home));
        }

        public IActionResult Reservations()
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");
            return View();
        }

        public IActionResult Logout()
        {
            HttpContext.Session.Clear();
            return RedirectToAction("Index", "Login");
        }

        private bool IsAdmin()
        {
            return !string.IsNullOrEmpty(HttpContext.Session.GetString("IdNumber"))
                && string.Equals(HttpContext.Session.GetString("Role"), "Admin", StringComparison.OrdinalIgnoreCase);
        }
    }
}