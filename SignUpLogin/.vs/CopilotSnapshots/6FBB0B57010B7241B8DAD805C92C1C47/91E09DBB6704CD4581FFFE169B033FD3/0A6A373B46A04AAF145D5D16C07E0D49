using Microsoft.AspNetCore.Mvc;
using SignUpLogin.Models;

namespace SignUpLogin.Controllers
{
    public class SignupController : Controller
    {
        public IActionResult Index()
        {
            return View("Signup");
        }

        [HttpPost]
        public IActionResult Index(Signup model)
        {
            return View("Signup", model);
        }
    }
}