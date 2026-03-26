using Microsoft.AspNetCore.Http;
using System.ComponentModel.DataAnnotations;

namespace SignUpLogin.Models.ViewModels
{
    public class ProfilePictureUploadViewModel
    {
        public string? CurrentImagePath { get; set; }

        [Display(Name = "Profile Picture")]
        public IFormFile? ProfileImage { get; set; }
    }
}
