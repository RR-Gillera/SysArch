using Microsoft.AspNetCore.Mvc;
using SignUpLogin.Models;

namespace SignUpLogin.Controllers
{
    public class LoginController : Controller
    {
        public IActionResult Index()
        {
            return View("Login", new Login());
        }

        [HttpPost]
        public IActionResult Index(Login model)
        {
            return View("Login", model);
        }
    }
}
