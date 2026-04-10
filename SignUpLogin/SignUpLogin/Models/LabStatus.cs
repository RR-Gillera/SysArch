using System.ComponentModel.DataAnnotations;

namespace SignUpLogin.Models
{
    public class LabStatus
    {
        [Key]
        public int Id { get; set; }

        [Required]
        [StringLength(50)]
        public string LabName { get; set; } = string.Empty;

        // e.g. "Available", "Occupied", "Closed", "Under Maintenance", etc.
        [Required]
        [StringLength(80)]
        public string Status { get; set; } = "Available";

        [StringLength(120)]
        public string? Remarks { get; set; }

        public DateTime UpdatedAt { get; set; } = DateTime.Now;
    }
}
