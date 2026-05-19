using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace SignUpLogin.Models
{
    public class Reservation
    {
        [Key]
        public int Id { get; set; }

        [Required]
        public string StudentIdNumber { get; set; } = string.Empty;

        [Required]
        [StringLength(50)]
        public string Laboratory { get; set; } = string.Empty;

        [StringLength(10)]
        public string? PcNumber { get; set; }

        [Required]
        [StringLength(120)]
        public string Purpose { get; set; } = string.Empty;

        /// <summary>Date the student wants to sit in (date portion only).</summary>
        [Required]
        public DateTime ReservationDate { get; set; }

        /// <summary>Pending | Approved | Denied</summary>
        [StringLength(20)]
        public string Status { get; set; } = "Pending";

        /// <summary>Optional admin note shown to the student.</summary>
        [StringLength(250)]
        public string? AdminRemarks { get; set; }

        public DateTime CreatedAt { get; set; } = DateTime.Now;

        public DateTime? ReviewedAt { get; set; }

        [ForeignKey(nameof(StudentIdNumber))]
        public Signup? Student { get; set; }
    }
}
