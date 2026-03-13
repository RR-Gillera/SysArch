using Microsoft.AspNetCore.Mvc;
using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace SignUpLogin.Models
{
    public class Signup
    {
        [Key]
        [Required(ErrorMessage = "ID Number is required.")]
        [RegularExpression(@"^\d{8}$", ErrorMessage = "ID Number must be exactly 8 digits.")]
        [Display(Name = "ID Number")]
        public string IdNumber { get; set; } = string.Empty;

        [Required(ErrorMessage = "First Name is required.")]
        [RegularExpression(@"^[a-zA-Z\s\-']+$", ErrorMessage = "First Name must contain letters only.")]
        [Display(Name = "First Name")]
        public string FirstName { get; set; } = string.Empty;

        [Required(ErrorMessage = "Last Name is required.")]
        [RegularExpression(@"^[a-zA-Z\s\-']+$", ErrorMessage = "Last Name must contain letters only.")]
        [Display(Name = "Last Name")]
        public string LastName { get; set; } = string.Empty;

        [RegularExpression(@"^[a-zA-Z\s\-']*$", ErrorMessage = "Middle Name must contain letters only.")]
        [Display(Name = "Middle Name")]
        public string MiddleName { get; set; } = string.Empty;

        [Required(ErrorMessage = "Course Level is required.")]
        [Display(Name = "Course Level")]
        public string CourseLevel { get; set; } = string.Empty;

        [Required(ErrorMessage = "Password is required.")]
        [RegularExpression(@"^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$",
            ErrorMessage = "Password must have at least 8 characters, an uppercase, a lowercase, a number, and a symbol.")]
        [DataType(DataType.Password)]
        public string Password { get; set; } = string.Empty;

        [NotMapped]
        [Required(ErrorMessage = "Please confirm your password.")]
        [DataType(DataType.Password)]
        [Compare("Password", ErrorMessage = "Passwords do not match.")]
        [Display(Name = "Confirm Password")]
        public string ConfirmPassword { get; set; } = string.Empty;

        [Required(ErrorMessage = "Email is required.")]
        [EmailAddress(ErrorMessage = "Enter a valid email address.")]
        [Display(Name = "Email Address")]
        public string Email { get; set; } = string.Empty;

        [Required(ErrorMessage = "Course is required.")]
        public string Course { get; set; } = string.Empty;

        [Required(ErrorMessage = "Address is required.")]
        public string Address { get; set; } = string.Empty;

        public string Role { get; set; } = "Student";

        public DateTime CreatedAt { get; set; } = DateTime.Now;
    }
}