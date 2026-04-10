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
        private const int DefaultSessions = 30; // adjust as needed

        public AdminController(ApplicationDbContext context)
        {
            _context = context;
        }

        // ?? Dashboard ??????????????????????????????????????????????????????????

        public async Task<IActionResult> Home()
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var currentSitIns = await _context.SitInRecords
                .Include(r => r.Student)
                .Where(r => r.TimeOut == null)
                .OrderByDescending(r => r.TimeIn)
                .Take(10)
                .ToListAsync();

            var announcements = await _context.Announcements
                .OrderByDescending(a => a.PostedAt)
                .Take(5)
                .ToListAsync();

            var vm = new AdminDashboardViewModel
            {
                UserName = HttpContext.Session.GetString("UserName") ?? "Admin",
                StudentsRegistered = await _context.Signups.CountAsync(s => s.Role == "Student"),
                CurrentlySitIn = currentSitIns.Count,
                TotalSitInRecords = await _context.SitInRecords.CountAsync(),
                CurrentSitIns = currentSitIns,
                Announcements = announcements
            };

            return View(vm);
        }

        // ?? Students page ??????????????????????????????????????????????????????

        public async Task<IActionResult> Students()
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var students = await _context.Signups
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
                .FirstOrDefaultAsync(s => s.IdNumber == idNumber && s.Role == "Student");

            if (student == null)
            {
                TempData["Error"] = "Student not found.";
                return RedirectToAction(nameof(Students));
            }

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

            var students = await _context.Signups
                .Where(s => s.Role == "Student")
                .ToListAsync();

            foreach (var s in students)
                s.RemainingSessions = DefaultSessions;

            await _context.SaveChangesAsync();

            TempData["Success"] = $"All student sessions reset to {DefaultSessions}.";
            return RedirectToAction(nameof(Students));
        }

        // ?? Sit-in ?????????????????????????????????????????????????????????????

        [HttpGet]
        public async Task<IActionResult> LookupStudent(string idNumber)
        {
            if (!IsAdmin()) return Unauthorized();

            if (string.IsNullOrWhiteSpace(idNumber))
                return Json(new { found = false });

            var student = await _context.Signups
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
                .FirstOrDefaultAsync(r => r.StudentIdNumber == StudentIdNumber && r.TimeOut == null);

            if (existing != null)
            {
                TempData["Error"] = "Student already has an active sit-in session.";
                return RedirectToAction(nameof(Home));
            }

            // Block if chosen PC is already taken
            if (!string.IsNullOrWhiteSpace(PcNumber))
            {
                var pcTaken = await _context.SitInRecords
                    .AnyAsync(r => r.Laboratory == Laboratory
                                && r.PcNumber == PcNumber
                                && r.TimeOut == null);

                if (pcTaken)
                {
                    TempData["Error"] = $"{PcNumber} in {Laboratory} is already occupied. Please choose another.";
                    return RedirectToAction(nameof(Home));
                }
            }

            var record = new SitInRecord
            {
                StudentIdNumber = StudentIdNumber,
                Purpose = Purpose,
                Laboratory = Laboratory,
                PcNumber = string.IsNullOrWhiteSpace(PcNumber) ? null : PcNumber,
                TimeIn = DateTime.Now
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

            record.TimeOut = DateTime.Now;
            await _context.SaveChangesAsync();

            TempData["Success"] = "Sit-in session ended successfully.";
            return RedirectToAction(nameof(Home));
        }

        // ?? Sit-in Records page

        public async Task<IActionResult> SitInRecords()
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var records = await _context.SitInRecords
                .Include(r => r.Student)
                .OrderByDescending(r => r.TimeIn)
                .ToListAsync();

            return View(records);
        }

        // ?? Announcements page ?????????????????????????????????????????????????

        public async Task<IActionResult> Announcements()
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var announcements = await _context.Announcements
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

        // ?? Other pages ????????????????????????????????????????????????????????

        public IActionResult Feedback()
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");
            return View();
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

        // ?? Helper ?????????????????????????????????????????????????????????????

        private bool IsAdmin()
        {
            return !string.IsNullOrEmpty(HttpContext.Session.GetString("IdNumber"))
                   && string.Equals(HttpContext.Session.GetString("Role"), "Admin", StringComparison.OrdinalIgnoreCase);
        }
    }
}