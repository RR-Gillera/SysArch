using Microsoft.AspNetCore.Mvc;
using SignUpLogin.Models;

namespace SignUpLogin.Controllers
{
    public class SignupController : Controller
    {
        public IActionResult Index()
        {
            return View("Signup"); // ✅ points to Views/Signup/Signup.cshtml
        }

        [HttpPost]
        public IActionResult Index(Signup model)
        {
            if (ModelState.IsValid)
            {
                return RedirectToAction("Index", "Home");
            }
            return View("Signup", model); // ✅ same here
        }
    }
}