using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using SignUpLogin.Data;
using SignUpLogin.Models;
using SignUpLogin.Models.ViewModels;
using QuestPDF.Fluent;
using QuestPDF.Helpers;
using QuestPDF.Infrastructure;

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

            if (student.RemainingSessions <= 0)
            {
                TempData["Error"] = $"{student.FirstName} {student.LastName} has no remaining sessions.";
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

        // ── Analytics ─────────────────────────────────────────────────────
        public async Task<IActionResult> Analytics()
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var totalSessions = await _context.SitInRecords.CountAsync();
            var activeSessions = await _context.SitInRecords.CountAsync(r => r.TimeOut == null);

            bool hasFeedbacks = await _context.Feedbacks.AnyAsync();
            double avgRating = hasFeedbacks
                ? await _context.Feedbacks.AverageAsync(f => (double)f.Rating)
                : 0.0;

            var totalFeedbacks = await _context.Feedbacks.CountAsync();

            var sessionsByLab = await _context.SitInRecords
                .GroupBy(r => r.Laboratory)
                .Select(g => new { Lab = g.Key, Count = g.Count() })
                .OrderByDescending(g => g.Count)
                .ToListAsync();

            // ── Leaderboard ────────────────────────────────────────────────────────

            // 1. All students
            var allStudents = await _context.Signups
                .AsNoTracking()
                .Where(s => s.Role == "Student")
                .ToListAsync();

            // 2. Sit-in time per student (completed sessions only, summed in memory)
            var completedRecords = await _context.SitInRecords
                .Where(r => r.TimeOut != null)
                .Select(r => new { r.StudentIdNumber, r.TimeIn, r.TimeOut })
                .ToListAsync();

            var sitInTimes = completedRecords
                .GroupBy(r => r.StudentIdNumber)
                .ToDictionary(
                    g => g.Key,
                    g => (int)g.Sum(r => (r.TimeOut!.Value - r.TimeIn).TotalMinutes)
                );

            // 3. Points data
            var pointsData = await _context.StudentPoints
                .AsNoTracking()
                .ToListAsync();

            var pointsDict = pointsData.ToDictionary(p => p.StudentIdNumber);

            // 4. Merge: every student gets an entry (0 points if no row yet)
            var leaderboardData = allStudents.Select(s =>
            {
                pointsDict.TryGetValue(s.IdNumber, out var sp);
                sitInTimes.TryGetValue(s.IdNumber, out int minutes);

                return new LeaderboardEntry
                {
                    StudentIdNumber = s.IdNumber,
                    StudentName = $"{s.FirstName} {s.LastName}".Trim(),
                    Points = sp?.Points ?? 0,
                    TotalMinutes = minutes,
                    LastRewardReason = sp?.LastRewardReason
                };
            })
            .OrderByDescending(e => e.Points)
            .ThenByDescending(e => e.TotalMinutes)
            .ToList();

            ViewBag.TotalSessions = totalSessions;
            ViewBag.ActiveSessions = activeSessions;
            ViewBag.AvgRating = avgRating.ToString("0.0");
            ViewBag.TotalFeedbacks = totalFeedbacks;
            ViewBag.SessionsByLab = sessionsByLab;
            ViewBag.LeaderboardData = leaderboardData;

            return View();
        }

        //  ── ADD POINTS ───────────────────────────────
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
                return RedirectToAction(nameof(Students)); // Redirect to Students list
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

            // Redirect back to Students list instead of Home/Dashboard
            return RedirectToAction(nameof(Students));
        }

        // ── Reservations ───────────────────────────────────────────────────
        public async Task<IActionResult> Reservations()
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var reservations = await _context.Reservations
                .AsNoTracking()
                .Include(r => r.Student)
                .OrderByDescending(r => r.CreatedAt)
                .ToListAsync();

            return View(reservations);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> ApproveReservation(int id, string? AdminRemarks)
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var reservation = await _context.Reservations.FindAsync(id);
            if (reservation == null)
            {
                TempData["Error"] = "Reservation not found.";
                return RedirectToAction(nameof(Reservations));
            }

            reservation.Status = "Approved";
            reservation.AdminRemarks = string.IsNullOrWhiteSpace(AdminRemarks) ? null : AdminRemarks.Trim();
            reservation.ReviewedAt = DateTime.UtcNow;
            await _context.SaveChangesAsync();

            TempData["Success"] = "Reservation approved successfully.";
            return RedirectToAction(nameof(Reservations));
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> DenyReservation(int id, string? AdminRemarks)
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var reservation = await _context.Reservations.FindAsync(id);
            if (reservation == null)
            {
                TempData["Error"] = "Reservation not found.";
                return RedirectToAction(nameof(Reservations));
            }

            reservation.Status = "Denied";
            reservation.AdminRemarks = string.IsNullOrWhiteSpace(AdminRemarks) ? null : AdminRemarks.Trim();
            reservation.ReviewedAt = DateTime.UtcNow;
            await _context.SaveChangesAsync();

            TempData["Error"] = "Reservation denied.";
            return RedirectToAction(nameof(Reservations));
        }

        // ── Export CSV ─────────────────────────────────────────────────────
        [HttpGet]
        public async Task<IActionResult> ExportSitInCsv()
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var records = await _context.SitInRecords
                .AsNoTracking()
                .Include(r => r.Student)
                .OrderByDescending(r => r.TimeIn)
                .ToListAsync();

            var sb = new System.Text.StringBuilder();
            sb.AppendLine("ID,Student ID,Student Name,Purpose,Laboratory,PC Number,Time In,Time Out,Duration (mins),Status");

            foreach (var r in records)
            {
                var name     = r.Student != null ? $"{r.Student.FirstName} {r.Student.LastName}".Trim() : r.StudentIdNumber;
                var timeIn   = r.TimeIn.ToLocalTime().ToString("yyyy-MM-dd HH:mm:ss");
                var timeOut  = r.TimeOut?.ToLocalTime().ToString("yyyy-MM-dd HH:mm:ss") ?? "Active";
                var duration = r.TimeOut.HasValue ? (int)(r.TimeOut.Value - r.TimeIn).TotalMinutes : 0;
                var status   = r.TimeOut == null ? "Active" : "Completed";
                var pc       = r.PcNumber ?? "";
                sb.AppendLine($"{r.Id},{r.StudentIdNumber},\"{name}\",\"{r.Purpose}\",\"{r.Laboratory}\",\"{pc}\",\"{timeIn}\",\"{timeOut}\",{duration},{status}");
            }

            var bytes = System.Text.Encoding.UTF8.GetBytes(sb.ToString());
            return File(bytes, "text/csv", $"SitInRecords_{DateTime.Now:yyyyMMdd_HHmmss}.csv");
        }

        [HttpGet]
        public async Task<IActionResult> ExportSitInDocx()
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var records = await _context.SitInRecords
                .AsNoTracking()
                .Include(r => r.Student)
                .OrderByDescending(r => r.TimeIn)
                .ToListAsync();

            using var memoryStream = new System.IO.MemoryStream();
            using (var wordDocument = DocumentFormat.OpenXml.Packaging.WordprocessingDocument.Create(memoryStream, DocumentFormat.OpenXml.WordprocessingDocumentType.Document))
            {
                var mainPart = wordDocument.AddMainDocumentPart();
                mainPart.Document = new DocumentFormat.OpenXml.Wordprocessing.Document();
                var body = mainPart.Document.AppendChild(new DocumentFormat.OpenXml.Wordprocessing.Body());

                // Add Title
                var para = body.AppendChild(new DocumentFormat.OpenXml.Wordprocessing.Paragraph());
                var run = para.AppendChild(new DocumentFormat.OpenXml.Wordprocessing.Run());
                run.AppendChild(new DocumentFormat.OpenXml.Wordprocessing.Text("Sit-in Records Report"));
                
                // Note: Building a full table in OpenXML is very verbose. For simplicity in this demo, 
                // we'll output formatted paragraphs. A full implementation would use DocumentFormat.OpenXml.Wordprocessing.Table
                foreach (var r in records)
                {
                    var name = r.Student != null ? $"{r.Student.FirstName} {r.Student.LastName}".Trim() : r.StudentIdNumber;
                    var timeIn = r.TimeIn.ToLocalTime().ToString("yyyy-MM-dd HH:mm:ss");
                    var timeOut = r.TimeOut?.ToLocalTime().ToString("yyyy-MM-dd HH:mm:ss") ?? "Active";
                    var status = r.TimeOut == null ? "Active" : "Completed";

                    var p = body.AppendChild(new DocumentFormat.OpenXml.Wordprocessing.Paragraph());
                    var r1 = p.AppendChild(new DocumentFormat.OpenXml.Wordprocessing.Run());
                    r1.AppendChild(new DocumentFormat.OpenXml.Wordprocessing.Text($"Student: {name} ({r.StudentIdNumber}) - Lab: {r.Laboratory} - Status: {status}"));
                    r1.AppendChild(new DocumentFormat.OpenXml.Wordprocessing.Break());
                    r1.AppendChild(new DocumentFormat.OpenXml.Wordprocessing.Text($"Time In: {timeIn} - Time Out: {timeOut}"));
                    
                    body.AppendChild(new DocumentFormat.OpenXml.Wordprocessing.Paragraph()); // spacer
                }
            }

            memoryStream.Position = 0;
            return File(memoryStream.ToArray(), "application/vnd.openxmlformats-officedocument.wordprocessingml.document", $"SitInRecords_{DateTime.Now:yyyyMMdd_HHmmss}.docx");
        }

        [HttpGet]
        public async Task<IActionResult> ExportSitInPdf()
        {
            if (!IsAdmin()) return RedirectToAction("Index", "Login");

            var records = await _context.SitInRecords
                .AsNoTracking()
                .Include(r => r.Student)
                .OrderByDescending(r => r.TimeIn)
                .ToListAsync();

            var document = QuestPDF.Fluent.Document.Create(container =>
            {
                container.Page(page =>
                {
                    page.Size(QuestPDF.Helpers.PageSizes.A4.Landscape());
                    page.Margin(1, QuestPDF.Infrastructure.Unit.Centimetre);
                    page.PageColor(QuestPDF.Helpers.Colors.White);
                    page.DefaultTextStyle(x => x.FontSize(10));

                    page.Header().Text("Sit-in Records Report").SemiBold().FontSize(16).FontColor(QuestPDF.Helpers.Colors.Blue.Darken2);

                    page.Content().PaddingVertical(1, QuestPDF.Infrastructure.Unit.Centimetre).Table(table =>
                    {
                        table.ColumnsDefinition(columns =>
                        {
                            columns.RelativeColumn(2); // ID/Name
                            columns.RelativeColumn(1); // Lab
                            columns.RelativeColumn(1); // PC
                            columns.RelativeColumn(2); // Time In
                            columns.RelativeColumn(2); // Time Out
                            columns.RelativeColumn(1); // Status
                        });

                        table.Header(header =>
                        {
                            header.Cell().Text("Student");
                            header.Cell().Text("Lab");
                            header.Cell().Text("PC");
                            header.Cell().Text("Time In");
                            header.Cell().Text("Time Out");
                            header.Cell().Text("Status");
                        });

                        foreach (var r in records)
                        {
                            var name = r.Student != null ? $"{r.Student.FirstName} {r.Student.LastName}".Trim() : r.StudentIdNumber;
                            var timeIn = r.TimeIn.ToLocalTime().ToString("yyyy-MM-dd HH:mm:ss");
                            var timeOut = r.TimeOut?.ToLocalTime().ToString("yyyy-MM-dd HH:mm:ss") ?? "Active";
                            var status = r.TimeOut == null ? "Active" : "Completed";

                            table.Cell().Text($"{name}\n({r.StudentIdNumber})");
                            table.Cell().Text(r.Laboratory);
                            table.Cell().Text(r.PcNumber ?? "-");
                            table.Cell().Text(timeIn);
                            table.Cell().Text(timeOut);
                            table.Cell().Text(status);
                        }
                    });

                    page.Footer().AlignCenter().Text(x =>
                    {
                        x.Span("Page ");
                        x.CurrentPageNumber();
                    });
                });
            });

            var pdfBytes = document.GeneratePdf();
            return File(pdfBytes, "application/pdf", $"SitInRecords_{DateTime.Now:yyyyMMdd_HHmmss}.pdf");
        }

        public IActionResult Logout()
        {
            HttpContext.Session.Clear();
            TempData["LoggedOut"] = "You have been logged out successfully.";
            return RedirectToAction("Index", "Login");
        }

        private bool IsAdmin()
        {
            return !string.IsNullOrEmpty(HttpContext.Session.GetString("IdNumber"))
                && string.Equals(HttpContext.Session.GetString("Role"), "Admin", StringComparison.OrdinalIgnoreCase);
        }
    }

    // Put this outside the AdminController class, in the same file or a separate file
    public class LeaderboardEntry
    {
        public string StudentIdNumber { get; set; } = string.Empty;
        public string StudentName { get; set; } = string.Empty;
        public int Points { get; set; }
        public int TotalMinutes { get; set; }
        public string? LastRewardReason { get; set; }
    }
}