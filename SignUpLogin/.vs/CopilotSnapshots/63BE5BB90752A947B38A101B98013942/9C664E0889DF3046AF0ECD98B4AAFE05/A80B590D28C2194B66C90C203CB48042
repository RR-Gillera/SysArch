using Microsoft.AspNetCore.Mvc;
namespace SignUpLogin.Controllers
{
    public class AdminController : Controller
    {
        public IActionResult Home()
        {
            if (string.IsNullOrEmpty(HttpContext.Session.GetString("IdNumber")) ||
                HttpContext.Session.GetString("Role") != "Admin")
                return RedirectToAction("Index", "Login");
            ViewBag.UserName = HttpContext.Session.GetString("UserName");
            return View();
        }
        public IActionResult Logout()
        {
            HttpContext.Session.Clear();
            return RedirectToAction("Index", "Login");
        }
    }
}
