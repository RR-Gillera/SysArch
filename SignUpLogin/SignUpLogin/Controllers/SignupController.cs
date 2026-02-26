using Microsoft.AspNetCore.Mvc;

namespace SignUpLogin.Controllers
{
    public class SignupController : Controller
    {
        public IActionResult Index()
        {
            return View();
        }
    }
}
