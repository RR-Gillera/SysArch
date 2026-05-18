using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using SignUpLogin.Data;
using SignUpLogin.Models;
using SignUpLogin.Models.ViewModels;

namespace SignUpLogin.Controllers
{
    public class LoginController : Controller
    {
        private readonly ApplicationDbContext _context;
        private const string RememberedIdCookie = "RememberedIdNumber";

        public LoginController(ApplicationDbContext context)
        {
            _context = context;
        }

        public async Task<IActionResult> Index()
        {
            var model = new Login();

            // Handle Remember Me Cookie
            if (Request.Cookies.TryGetValue(RememberedIdCookie, out var rememberedIdNumber) && !string.IsNullOrWhiteSpace(rememberedIdNumber))
            {
                model.IdNumber = rememberedIdNumber;
                model.RememberMe = true;
            }

            // Fetch Top 5 Students for Leaderboard
            var topStudents = await _context.StudentPoints
                .AsNoTracking()
                .Include(p => p.Student)
                .OrderByDescending(p => p.Points)
                .Take(5)
                .Select(p => new LeaderboardEntryViewModel
                {
                    StudentIdNumber = p.StudentIdNumber,
                    StudentName = p.Student != null ? $"{p.Student.FirstName} {p.Student.LastName}".Trim() : p.StudentIdNumber,
                    Points = p.Points,
                    LifetimePoints = p.LifetimePoints
                })
                .ToListAsync();

            ViewBag.LeaderboardData = topStudents;

            return View("Login", model);
        }

        [HttpPost]
        public async Task<IActionResult> Index(Login model)
        {
            if (!ModelState.IsValid)
            {
                // Re-fetch leaderboard if validation fails so it still shows
                ViewBag.LeaderboardData = await GetLeaderboardData();
                return View("Login", model);
            }

            // Try by IdNumber first (students), then by FirstName for admins
            var user = await _context.Signups
                           .FirstOrDefaultAsync(u => u.IdNumber == model.IdNumber)
                       ?? await _context.Signups
                           .FirstOrDefaultAsync(u => u.FirstName == model.IdNumber && u.Role == "Admin");

            if (user == null || !BCrypt.Net.BCrypt.Verify(model.Password, user.Password))
            {
                ModelState.AddModelError(string.Empty, "Invalid ID Number/Username or Password.");
                // Re-fetch leaderboard if login fails
                ViewBag.LeaderboardData = await GetLeaderboardData();
                return View("Login", model);
            }

            HttpContext.Session.Clear();

            if (model.RememberMe)
            {
                Response.Cookies.Append(RememberedIdCookie, model.IdNumber, new CookieOptions
                {
                    Expires = DateTimeOffset.UtcNow.AddDays(30),
                    HttpOnly = true,
                    IsEssential = true,
                    Secure = Request.IsHttps,
                    SameSite = SameSiteMode.Lax
                });
            }
            else
            {
                Response.Cookies.Delete(RememberedIdCookie);
            }

            HttpContext.Session.SetString("UserName", $"{user.FirstName} {user.LastName}");
            HttpContext.Session.SetString("IdNumber", user.IdNumber);
            HttpContext.Session.SetString("Course", user.Course);
            HttpContext.Session.SetString("CourseLevel", user.CourseLevel);
            HttpContext.Session.SetString("Email", user.Email);
            HttpContext.Session.SetString("Role", user.Role);
            HttpContext.Session.SetString("ProfileImagePath", user.ProfileImagePath ?? string.Empty);

            TempData["LoginSuccess"] = $"Welcome back, {user.FirstName}!";

            if (user.Role == "Admin")
            {
                return RedirectToAction("Home", "Admin");
            }
            else
            {
                return RedirectToAction("Index", "Home");
            }
        }

        public IActionResult Logout()
        {
            HttpContext.Session.Clear();
            TempData["LoggedOut"] = "You have been logged out successfully.";
            return RedirectToAction("Index");
        }

        // Helper method to avoid code duplication
        private async Task<List<LeaderboardEntryViewModel>> GetLeaderboardData()
        {
            return await _context.StudentPoints
                .AsNoTracking()
                .Include(p => p.Student)
                .OrderByDescending(p => p.Points)
                .Take(5)
                .Select(p => new LeaderboardEntryViewModel
                {
                    StudentIdNumber = p.StudentIdNumber,
                    StudentName = p.Student != null ? $"{p.Student.FirstName} {p.Student.LastName}".Trim() : p.StudentIdNumber,
                    Points = p.Points,
                    LifetimePoints = p.LifetimePoints
                })
                .ToListAsync();
        }
    }
}