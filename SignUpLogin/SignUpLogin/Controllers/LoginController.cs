using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using SignUpLogin.Data;
using SignUpLogin.Models;

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

        public IActionResult Index()
        {
            var model = new Login();
            if (Request.Cookies.TryGetValue(RememberedIdCookie, out var rememberedIdNumber) && !string.IsNullOrWhiteSpace(rememberedIdNumber))
            {
                model.IdNumber = rememberedIdNumber;
                model.RememberMe = true;
            }
            return View("Login", model);
        }

        [HttpPost]
        public async Task<IActionResult> Index(Login model)
        {
            if (!ModelState.IsValid)
                return View("Login", model);

            // Try by IdNumber first (students), then by FirstName for admins
            var user = await _context.Signups
                           .FirstOrDefaultAsync(u => u.IdNumber == model.IdNumber)
                       ?? await _context.Signups
                           .FirstOrDefaultAsync(u => u.FirstName == model.IdNumber && u.Role == "Admin");

            if (user == null || !BCrypt.Net.BCrypt.Verify(model.Password, user.Password))
            {
                ModelState.AddModelError(string.Empty, "Invalid ID Number/Username or Password.");
                return View("Login", model);
            }

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

            TempData["Success"] = "Login successful!";

            if (user.Role == "Admin")
                return RedirectToAction("Home", "Admin");
            else
                return RedirectToAction("Home");
        }

        public IActionResult Logout()
        {
            HttpContext.Session.Clear();
            return RedirectToAction("Index");
        }
    }
}