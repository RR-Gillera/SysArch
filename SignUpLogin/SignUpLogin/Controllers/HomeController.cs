using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using SignUpLogin.Data;
using SignUpLogin.Models;
using SignUpLogin.Models.ViewModels;
using System.Diagnostics;

namespace SignUpLogin.Controllers
{
    public class HomeController : Controller
    {
        private readonly ApplicationDbContext _context;

        private static readonly string[] LabNames = { "Lab 524", "Lab 526", "Lab 528", "Lab 542", "Lab 544" };

        public HomeController(ApplicationDbContext context)
        {
            _context = context;
        }

        public async Task<IActionResult> Index()
        {
            var guardResult = EnsureStudentAccess();
            if (guardResult != null)
                return guardResult;

            var idNumber = HttpContext.Session.GetString("IdNumber")!;

            var student = await _context.Signups.FirstOrDefaultAsync(s => s.IdNumber == idNumber);
            if (student == null)
            {
                HttpContext.Session.Clear();
                return RedirectToAction("Index", "Login");
            }

            if (student.RemainingSessions <= 0)
            {
                student.RemainingSessions = 30;
                await _context.SaveChangesAsync();
            }

            var recentHistory = await _context.SitInRecords
                .Where(r => r.StudentIdNumber == idNumber)
                .OrderByDescending(r => r.TimeIn)
                .Take(5)
                .ToListAsync();

            var announcements = await _context.Announcements
                .OrderByDescending(a => a.PostedAt)
                .Take(5)
                .ToListAsync();

            var unreadCount = await _context.Announcements
                .CountAsync(a => !student.LastAnnouncementsReadAt.HasValue || a.PostedAt > student.LastAnnouncementsReadAt.Value);

            HttpContext.Session.SetInt32("UnreadAnnouncements", unreadCount);

            var activeSitIn = await _context.SitInRecords
                .FirstOrDefaultAsync(r => r.StudentIdNumber == idNumber && r.TimeOut == null);

            var pointsRow = await _context.StudentPoints
                .FirstOrDefaultAsync(p => p.StudentIdNumber == idNumber);

            // Load lab statuses set by admin; seed any missing labs as Available
            var labStatuses = await _context.LabStatuses.ToListAsync();
            bool anyAdded = false;
            foreach (var name in LabNames)
            {
                if (!labStatuses.Any(l => l.LabName == name))
                {
                    var newLab = new LabStatus { LabName = name, Status = "Available", UpdatedAt = DateTime.Now };
                    _context.LabStatuses.Add(newLab);
                    labStatuses.Add(newLab);
                    anyAdded = true;
                }
            }
            if (anyAdded) await _context.SaveChangesAsync();

            var orderedLabStatuses = LabNames
                .Select(n => labStatuses.First(l => l.LabName == n))
                .ToList();

            var vm = new StudentHomeViewModel
            {
                UserName = $"{student.FirstName} {student.LastName}".Trim(),
                IdNumber = student.IdNumber,
                Course = student.Course,
                CourseLevel = student.CourseLevel,
                Email = student.Email,
                ProfileImagePath = student.ProfileImagePath,
                RemainingSessions = student.RemainingSessions,
                UnreadAnnouncementsCount = unreadCount,
                Announcements = announcements,
                RecentSitInHistory = recentHistory,
                IsCurrentlyActive = activeSitIn != null,
                ActiveSitIn = activeSitIn,
                Points = pointsRow?.Points ?? 0,
                LastRewardReason = pointsRow?.LastRewardReason,
                LabStatuses = orderedLabStatuses,
                SubmittedFeedbacks = await _context.Feedbacks
                    .Where(f => f.StudentIdNumber == idNumber)
                    .Select(f => new Feedback { SitInRecordId = f.SitInRecordId })
                    .ToListAsync()
            };

            HttpContext.Session.SetString("ProfileImagePath", student.ProfileImagePath ?? string.Empty);

            return View(vm);
        }

        public Task<IActionResult> Home()
        {
            return Index();
        }

        public Task<IActionResult> Dashboard()
        {
            return Index();
        }

        // ── Get occupied PCs for a lab (student-facing, read-only) ──────────────
        [HttpGet]
        public async Task<IActionResult> GetOccupiedPcs(string laboratory)
        {
            var guardResult = EnsureStudentAccess();
            if (guardResult != null) return Unauthorized();

            if (string.IsNullOrWhiteSpace(laboratory))
                return Json(new { occupiedPcs = Array.Empty<string>() });

            var occupiedPcs = await _context.SitInRecords
                .Where(r => r.Laboratory == laboratory && r.TimeOut == null && r.PcNumber != null)
                .Select(r => r.PcNumber)
                .ToListAsync();

            return Json(new { occupiedPcs });
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> MarkAnnouncementsRead()
        {
            var guardResult = EnsureStudentAccess();
            if (guardResult != null)
                return guardResult;

            var idNumber = HttpContext.Session.GetString("IdNumber")!;
            var student = await _context.Signups.FirstOrDefaultAsync(s => s.IdNumber == idNumber);
            if (student != null)
            {
                student.LastAnnouncementsReadAt = DateTime.UtcNow;
                await _context.SaveChangesAsync();
                HttpContext.Session.SetInt32("UnreadAnnouncements", 0);
            }

            return RedirectToAction(nameof(Index));
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> SubmitFeedback(int SitInRecordId, int Rating, string? Comment)
        {
            var guardResult = EnsureStudentAccess();
            if (guardResult != null) return guardResult;

            var idNumber = HttpContext.Session.GetString("IdNumber")!;

            var alreadySubmitted = await _context.Feedbacks
                .AnyAsync(f => f.StudentIdNumber == idNumber && f.SitInRecordId == SitInRecordId);

            if (!alreadySubmitted && Rating >= 1 && Rating <= 5)
            {
                _context.Feedbacks.Add(new Feedback
                {
                    StudentIdNumber = idNumber,
                    SitInRecordId = SitInRecordId,
                    Rating = Rating,
                    Comment = string.IsNullOrWhiteSpace(Comment) ? null : Comment.Trim(),
                    SubmittedAt = DateTime.Now
                });
                await _context.SaveChangesAsync();
                TempData["Success"] = "Thank you for your feedback!";
            }

            return RedirectToAction(nameof(Index));
        }

        public IActionResult Privacy()
        {
            return View();
        }

        [ResponseCache(Duration = 0, Location = ResponseCacheLocation.None, NoStore = true)]
        public IActionResult Error()
        {
            return View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
        }

        private IActionResult? EnsureStudentAccess()
        {
            var idNumber = HttpContext.Session.GetString("IdNumber");
            var role = HttpContext.Session.GetString("Role");

            if (string.IsNullOrEmpty(idNumber))
                return RedirectToAction("Index", "Login");

            if (!string.Equals(role, "Student", StringComparison.OrdinalIgnoreCase))
                return RedirectToAction("Home", "Admin");

            return null;
        }
    }
}
