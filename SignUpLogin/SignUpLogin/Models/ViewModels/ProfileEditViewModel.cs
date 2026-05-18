using System.ComponentModel.DataAnnotations;

namespace SignUpLogin.Models.ViewModels
{
    public class ProfileEditViewModel
    {
        [Required]
        [Display(Name = "ID Number")]
        public string IdNumber { get; set; } = string.Empty;

        [Required]
        [Display(Name = "First Name")]
        public string FirstName { get; set; } = string.Empty;

        [Display(Name = "Middle Name")]
        public string? MiddleName { get; set; }

        [Required]
        [Display(Name = "Last Name")]
        public string LastName { get; set; } = string.Empty;

        [Required]
        [Display(Name = "Email")]
        [EmailAddress]
        public string Email { get; set; } = string.Empty;

        [Required]
        [Display(Name = "Course")]
        public string Course { get; set; } = string.Empty;

        [Required]
        [Display(Name = "Year Level")]
        public string CourseLevel { get; set; } = string.Empty;

        [Required]
        [Display(Name = "Address")]
        public string Address { get; set; } = string.Empty;
    }
}
