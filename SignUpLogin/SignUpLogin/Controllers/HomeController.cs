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
        private const int MinutesPerPoint = 120;
        private const int PointsPerSession = 3;

        public HomeController(ApplicationDbContext context)
        {
            _context = context;
        }

        public async Task<IActionResult> Index()
        {
            var guardResult = EnsureStudentAccess();
            if (guardResult != null) return guardResult;

            var idNumber = HttpContext.Session.GetString("IdNumber")!;
            var student = await _context.Signups.FirstOrDefaultAsync(s => s.IdNumber == idNumber);
            if (student == null)
            {
                HttpContext.Session.Clear();
                return RedirectToAction("Index", "Login");
            }


            var recentHistory = await _context.SitInRecords
                 .Where(r => r.StudentIdNumber == idNumber)
                .OrderByDescending(r => r.TimeIn)
                .Take(5)
                .ToListAsync();

            var announcements = await _context.Announcements.OrderByDescending(a => a.PostedAt).Take(5).ToListAsync();
            var unreadCount = await _context.Announcements.CountAsync(a => !student.LastAnnouncementsReadAt.HasValue || a.PostedAt > student.LastAnnouncementsReadAt.Value);
            HttpContext.Session.SetInt32("UnreadAnnouncements", unreadCount);

            var activeSitIn = await _context.SitInRecords.FirstOrDefaultAsync(r => r.StudentIdNumber == idNumber && r.TimeOut == null);
            var pointsRow = await _context.StudentPoints.FirstOrDefaultAsync(p => p.StudentIdNumber == idNumber);

            // Auto-award points
            var unawarded = await _context.SitInRecords.Where(r => r.StudentIdNumber == idNumber && r.TimeOut != null && !r.PointsAwarded).ToListAsync();
            int newPoints = 0;
            foreach (var record in unawarded)
            {
                int earned = (int)((record.TimeOut!.Value - record.TimeIn).TotalMinutes / MinutesPerPoint);
                newPoints += earned;
                record.PointsAwarded = true;
            }

            if (unawarded.Any())
            {
                if (newPoints > 0)
                {
                    if (pointsRow == null)
                    {
                        pointsRow = new StudentPoints
                        {
                            StudentIdNumber = idNumber,
                            Points = newPoints,
                            LifetimePoints = newPoints,
                            LastRewardReason = $"Earned {newPoints} pt(s) for sit-in time",
                            UpdatedAt = DateTime.UtcNow
                        };
                        _context.StudentPoints.Add(pointsRow);
                    }
                    else
                    {
                        pointsRow.Points += newPoints;
                        pointsRow.LifetimePoints += newPoints;
                        pointsRow.LastRewardReason = $"Earned {newPoints} pt(s) for sit-in time";
                        pointsRow.UpdatedAt = DateTime.UtcNow;
                    }
                    TempData["PointsEarned"] = newPoints;
                }
                await _context.SaveChangesAsync();
            }

            if (pointsRow != null && pointsRow.LifetimePoints < pointsRow.Points)
            {
                pointsRow.LifetimePoints = pointsRow.Points;
                pointsRow.UpdatedAt = DateTime.UtcNow;
                await _context.SaveChangesAsync();
            }

            // Lab statuses
            var labStatuses = await _context.LabStatuses.ToListAsync();
            bool anyAdded = false;
            foreach (var name in LabNames)
            {
                if (!labStatuses.Any(l => l.LabName == name))
                {
                    _context.LabStatuses.Add(new LabStatus { LabName = name, Status = "Available", UpdatedAt = DateTime.UtcNow });
                    anyAdded = true;
                }
            }
            if (anyAdded) await _context.SaveChangesAsync();
            var orderedLabStatuses = LabNames.Select(n => labStatuses.First(l => l.LabName == n)).ToList();

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
                LifetimePoints = pointsRow?.LifetimePoints ?? 0,
                LastRewardReason = pointsRow?.LastRewardReason,
                LabStatuses = orderedLabStatuses
            };

            // Add Leaderboard Data for Student Dashboard
            var topStudents = await _context.StudentPoints
                .AsNoTracking()
                .Include(p => p.Student)
                .OrderByDescending(p => p.Points)
                .Take(5)
                .ToListAsync();

            ViewBag.LeaderboardData = topStudents.Select(p => new LeaderboardEntryViewModel
            {
                StudentIdNumber = p.StudentIdNumber,
                StudentName = p.Student != null
                                    ? $"{p.Student.FirstName} {p.Student.LastName}".Trim()
                                    : p.StudentIdNumber,
                Points = p.Points,
                LifetimePoints = p.LifetimePoints
            }).ToList();

            HttpContext.Session.SetString("ProfileImagePath", student.ProfileImagePath ?? string.Empty);
            return View(vm);
        }

        // ── Sit-in History Page ──────────────────────────────────────
        public async Task<IActionResult> SitInHistory()
        {
            var guardResult = EnsureStudentAccess();
            if (guardResult != null) return guardResult;

            var idNumber = HttpContext.Session.GetString("IdNumber")!;
            var records = await _context.SitInRecords
                .Where(r => r.StudentIdNumber == idNumber)
                .OrderByDescending(r => r.TimeIn)
                .ToListAsync();

            // Track which records already have feedback
            var submittedIds = await _context.Feedbacks
                .Where(f => f.StudentIdNumber == idNumber)
                .Select(f => f.SitInRecordId)
                .ToHashSetAsync();

            ViewBag.SubmittedFeedbackIds = submittedIds;
            return View(records);
        }

        public Task<IActionResult> Home() => Index();
        public Task<IActionResult> Dashboard() => Index();

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> RedeemPoints(int redeemAmount)
        {
            var guardResult = EnsureStudentAccess();
            if (guardResult != null) return guardResult;

            var idNumber = HttpContext.Session.GetString("IdNumber")!;
            if (redeemAmount < PointsPerSession)
            {
                TempData["RedeemError"] = $"Minimum redemption is {PointsPerSession} points (1 session).";
                return RedirectToAction(nameof(Index));
            }

            int validPoints = (redeemAmount / PointsPerSession) * PointsPerSession;
            int sessionsGained = validPoints / PointsPerSession;
            var pointsRow = await _context.StudentPoints.FirstOrDefaultAsync(p => p.StudentIdNumber == idNumber);

            if (pointsRow == null || pointsRow.Points < validPoints)
            {
                TempData["RedeemError"] = "You don't have enough points to redeem.";
                return RedirectToAction(nameof(Index));
            }

            var student = await _context.Signups.FirstOrDefaultAsync(s => s.IdNumber == idNumber);
            if (student == null)
            {
                HttpContext.Session.Clear();
                return RedirectToAction("Index", "Login");
            }

            pointsRow.Points -= validPoints;
            pointsRow.LastRewardReason = $"Redeemed {validPoints} pt(s) for {sessionsGained} session(s)";
            pointsRow.UpdatedAt = DateTime.UtcNow;
            student.RemainingSessions += sessionsGained;

            await _context.SaveChangesAsync();
            TempData["RedeemSuccess"] = $"Redeemed {validPoints} points for {sessionsGained} extra session(s)!";
            return RedirectToAction(nameof(Index));
        }

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
            if (guardResult != null) return guardResult;

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
            var alreadySubmitted = await _context.Feedbacks.AnyAsync(f => f.StudentIdNumber == idNumber && f.SitInRecordId == SitInRecordId);

            if (!alreadySubmitted && Rating >= 1 && Rating <= 5)
            {
                _context.Feedbacks.Add(new Feedback
                {
                    StudentIdNumber = idNumber,
                    SitInRecordId = SitInRecordId,
                    Rating = Rating,
                    Comment = string.IsNullOrWhiteSpace(Comment) ? null : Comment.Trim(),
                    SubmittedAt = DateTime.UtcNow
                });
                await _context.SaveChangesAsync();
                TempData["Success"] = "Thank you for your feedback!";
            }

            // Redirect back to history page instead of Index
            return RedirectToAction(nameof(SitInHistory));
        }

        // ── Reservations ──────────────────────────────────────────────────
        public async Task<IActionResult> Reservations()
        {
            var guardResult = EnsureStudentAccess();
            if (guardResult != null) return guardResult;

            var idNumber = HttpContext.Session.GetString("IdNumber")!;
            var reservations = await _context.Reservations
                .Where(r => r.StudentIdNumber == idNumber)
                .OrderByDescending(r => r.CreatedAt)
                .ToListAsync();

            return View(reservations);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> MakeReservation(
            string Laboratory, string? PcNumber, string Purpose, DateTime ReservationDate)
        {
            var guardResult = EnsureStudentAccess();
            if (guardResult != null) return guardResult;

            var idNumber = HttpContext.Session.GetString("IdNumber")!;
            if (string.IsNullOrWhiteSpace(Laboratory) || string.IsNullOrWhiteSpace(Purpose))
            {
                TempData["Error"] = "All fields are required.";
                return RedirectToAction(nameof(Reservations));
            }

            var hasPending = await _context.Reservations.AnyAsync(r =>
                r.StudentIdNumber == idNumber &&
                r.Status == "Pending" &&
                r.ReservationDate.Date == ReservationDate.Date);

            if (hasPending)
            {
                TempData["Error"] = "You already have a pending reservation on that date.";
                return RedirectToAction(nameof(Reservations));
            }

            _context.Reservations.Add(new Reservation
            {
                StudentIdNumber = idNumber,
                Laboratory      = Laboratory,
                PcNumber        = string.IsNullOrWhiteSpace(PcNumber) ? null : PcNumber.Trim(),
                Purpose         = Purpose.Trim(),
                ReservationDate = ReservationDate.Date,
                CreatedAt       = DateTime.UtcNow
            });

            await _context.SaveChangesAsync();
            TempData["Success"] = "Reservation submitted! Please wait for admin approval.";
            return RedirectToAction(nameof(Reservations));
        }

        public IActionResult Privacy() => View();

        [ResponseCache(Duration = 0, Location = ResponseCacheLocation.None, NoStore = true)]
        public IActionResult Error() => View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });

        private IActionResult? EnsureStudentAccess()
        {
            var idNumber = HttpContext.Session.GetString("IdNumber");
            var role = HttpContext.Session.GetString("Role");
            if (string.IsNullOrEmpty(idNumber)) return RedirectToAction("Index", "Login");
            if (!string.Equals(role, "Student", StringComparison.OrdinalIgnoreCase)) return RedirectToAction("Home", "Admin");
            return null;
        }
    }
}