using System.ComponentModel.DataAnnotations;

namespace SignUpLogin.Models
{
    public class Announcement
    {
        [Key]
        public int Id { get; set; }

        [Required]
        [StringLength(120)]
        public string Title { get; set; } = string.Empty;

        [Required]
        [StringLength(1000)]
        public string Message { get; set; } = string.Empty;

        [Required]
        [StringLength(80)]
        public string PostedBy { get; set; } = "CCS Admin";

        public DateTime PostedAt { get; set; } = DateTime.UtcNow;
    }
}
