using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using SignUpLogin.Models;
using SignUpLogin.Data;

namespace SignUpLogin.Controllers
{
    public class SignupController : Controller
    {
        private readonly ApplicationDbContext _context;

        public SignupController(ApplicationDbContext context)
        {
            _context = context;
        }

        public IActionResult Index()
        {
            return View("Signup");
        }

        [HttpPost]
        public async Task<IActionResult> Index(Signup model)
        {
            if (ModelState.IsValid)
            {
                bool idExists = await _context.Signups.AnyAsync(u => u.IdNumber == model.IdNumber);
                if (idExists)
                {
                    ModelState.AddModelError("IdNumber", "This ID Number is already registered.");
                    return View("Signup", model);
                }

                bool emailExists = await _context.Signups.AnyAsync(u => u.Email == model.Email);
                if (emailExists)
                {
                    ModelState.AddModelError("Email", "This Email is already registered.");
                    return View("Signup", model);
                }

                model.Role = "Student";
                model.Password = BCrypt.Net.BCrypt.HashPassword(model.Password);
                _context.Signups.Add(model);
                await _context.SaveChangesAsync();
                TempData["Success"] = "Registration successful!";
                return RedirectToAction("Index", "Login");
            }
            return View("Signup", model);
        }
    }
}