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

            await ProcessDueReservations();

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
                int earned = 1;
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
                            LastRewardReason = $"Earned {newPoints} pt(s) for completing sit-in(s)",
                            UpdatedAt = DateTime.Now
                        };
                        _context.StudentPoints.Add(pointsRow);
                    }
                    else
                    {
                        pointsRow.Points += newPoints;
                        pointsRow.LifetimePoints += newPoints;
                        pointsRow.LastRewardReason = $"Earned {newPoints} pt(s) for completing sit-in(s)";
                        pointsRow.UpdatedAt = DateTime.Now;
                    }
                    TempData["PointsEarned"] = newPoints;
                }
                await _context.SaveChangesAsync();
            }

            if (pointsRow != null && pointsRow.LifetimePoints < pointsRow.Points)
            {
                pointsRow.LifetimePoints = pointsRow.Points;
                pointsRow.UpdatedAt = DateTime.Now;
                await _context.SaveChangesAsync();
            }

            // Lab statuses
            var labStatuses = await _context.LabStatuses.ToListAsync();
            bool anyAdded = false;
            foreach (var name in LabNames)
            {
                if (!labStatuses.Any(l => l.LabName == name))
                {
                    _context.LabStatuses.Add(new LabStatus { LabName = name, Status = "Available", UpdatedAt = DateTime.Now });
                    anyAdded = true;
                }
            }
            if (anyAdded) await _context.SaveChangesAsync();
            var orderedLabStatuses = labStatuses.OrderBy(l => l.LabName).ToList();

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
            pointsRow.UpdatedAt = DateTime.Now;
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
                return Json(new { occupiedPcs = Array.Empty<string>(), unavailablePcs = Array.Empty<string>() });

            var occupiedPcs = await _context.SitInRecords
                .Where(r => r.Laboratory == laboratory && r.TimeOut == null && r.PcNumber != null)
                .Select(r => r.PcNumber)
                .ToListAsync();

            var labStatus = await _context.LabStatuses
                .FirstOrDefaultAsync(l => l.LabName == laboratory);

            var unavailablePcs = labStatus?.UnavailablePcs?.Split(',', StringSplitOptions.RemoveEmptyEntries) ?? Array.Empty<string>();

            return Json(new { occupiedPcs, unavailablePcs });
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
                student.LastAnnouncementsReadAt = DateTime.Now;
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
                    SubmittedAt = DateTime.Now
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

            await ProcessDueReservations();

            var idNumber = HttpContext.Session.GetString("IdNumber")!;
            var reservations = await _context.Reservations
                .Where(r => r.StudentIdNumber == idNumber)
                .OrderByDescending(r => r.CreatedAt)
                .ToListAsync();

            var labStatuses = await _context.LabStatuses.ToListAsync();
            ViewBag.LabNames = labStatuses.OrderBy(l => l.LabName).Select(l => l.LabName).ToList();
            ViewBag.LabPcCountMap = labStatuses.ToDictionary(l => l.LabName, l => l.TotalPCs);

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

            if (ReservationDate < DateTime.Now)
            {
                TempData["Error"] = "Reservation date cannot be in the past.";
                return RedirectToAction(nameof(Reservations));
            }

            var student = await _context.Signups.FirstOrDefaultAsync(s => s.IdNumber == idNumber);
            if (student == null || student.RemainingSessions <= 0)
            {
                TempData["Error"] = "You do not have enough remaining sessions to make a reservation.";
                return RedirectToAction(nameof(Reservations));
            }

            var hasPending = await _context.Reservations.AnyAsync(r =>
                r.StudentIdNumber == idNumber &&
                (r.Status == "Pending" || r.Status == "Approved") &&
                r.ReservationDate.Date == ReservationDate.Date);

            if (hasPending)
            {
                TempData["Error"] = "You already have a pending or approved reservation on that date.";
                return RedirectToAction(nameof(Reservations));
            }

            // Check if another student has already reserved this specific PC at the same time
            if (!string.IsNullOrWhiteSpace(PcNumber))
            {
                var cleanPc = PcNumber.Trim();
                
                // 1. Check if the PC is marked as unavailable by admin
                var labStatus = await _context.LabStatuses.FirstOrDefaultAsync(l => l.LabName == Laboratory);
                if (labStatus != null && !string.IsNullOrWhiteSpace(labStatus.UnavailablePcs))
                {
                    var unavailableList = labStatus.UnavailablePcs.Split(',', StringSplitOptions.RemoveEmptyEntries);
                    if (unavailableList.Contains(cleanPc))
                    {
                        TempData["Error"] = $"{cleanPc} is currently unavailable in {Laboratory}.";
                        return RedirectToAction(nameof(Reservations));
                    }
                }

                // Check if any approved reservation exists for the same Lab, PC, and Time (within a +/- 30 min window for simplicity, or exact if preferred)
                // For now, let's check for the exact same hour/minute slot or overlapping ones
                var conflictRangeStart = ReservationDate.AddMinutes(-30);
                var conflictRangeEnd = ReservationDate.AddMinutes(30);

                var isReserved = await _context.Reservations.AnyAsync(r => 
                    r.Status == "Approved" && 
                    r.Laboratory == Laboratory && 
                    r.PcNumber == cleanPc &&
                    r.ReservationDate >= conflictRangeStart && 
                    r.ReservationDate <= conflictRangeEnd);

                if (isReserved)
                {
                    TempData["Error"] = $"{cleanPc} in {Laboratory} is already reserved for this time. Please choose another PC or time.";
                    return RedirectToAction(nameof(Reservations));
                }
            }

            _context.Reservations.Add(new Reservation
            {
                StudentIdNumber = idNumber,
                Laboratory      = Laboratory,
                PcNumber        = string.IsNullOrWhiteSpace(PcNumber) ? null : PcNumber.Trim(),
                Purpose         = Purpose.Trim(),
                ReservationDate = ReservationDate,
                CreatedAt       = DateTime.Now
            });

            await _context.SaveChangesAsync();
            TempData["Success"] = "Reservation submitted! Please wait for admin approval.";
            return RedirectToAction(nameof(Reservations));
        }

        public IActionResult Privacy() => View();

        [ResponseCache(Duration = 0, Location = ResponseCacheLocation.None, NoStore = true)]
        public IActionResult Error() => View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });

        private async Task ProcessDueReservations()
        {
            var now = DateTime.Now;
            var dueReservations = await _context.Reservations
                .Where(r => r.Status == "Approved" && r.ReservationDate <= now)
                .ToListAsync();

            if (dueReservations.Any())
            {
                bool changes = false;
                foreach (var res in dueReservations)
                {
                    var student = await _context.Signups.FirstOrDefaultAsync(s => s.IdNumber == res.StudentIdNumber);
                    if (student != null && student.RemainingSessions > 0)
                    {
                        var existingActive = await _context.SitInRecords.AnyAsync(r => r.StudentIdNumber == res.StudentIdNumber && r.TimeOut == null);
                        if (!existingActive)
                        {
                            var isPcOccupied = false;
                            if (!string.IsNullOrWhiteSpace(res.PcNumber))
                            {
                                isPcOccupied = await _context.SitInRecords.AnyAsync(r => r.Laboratory == res.Laboratory && r.PcNumber == res.PcNumber && r.TimeOut == null);
                            }

                            if (!isPcOccupied)
                            {
                                var sitInRecord = new SitInRecord
                                {
                                    StudentIdNumber = res.StudentIdNumber,
                                    Purpose = res.Purpose,
                                    Laboratory = res.Laboratory,
                                    PcNumber = res.PcNumber,
                                    TimeIn = res.ReservationDate
                                };

                                _context.SitInRecords.Add(sitInRecord);
                                student.RemainingSessions--;
                                
                                res.Status = "CheckedIn";
                                changes = true;
                            }
                        }
                    }
                    else if (student != null && student.RemainingSessions <= 0)
                    {
                        res.Status = "CheckedIn";
                        changes = true;
                    }
                }

                if (changes)
                {
                    await _context.SaveChangesAsync();
                }
            }
        }

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
