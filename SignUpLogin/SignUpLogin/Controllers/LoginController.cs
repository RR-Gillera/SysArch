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
            if (ModelState.IsValid)
            {
                // TODO: Add authentication logic here
                // This will be implemented with database and authentication
                return RedirectToAction("Index", "Home");
            }
            return View("Login", model);
        }
    }
}
