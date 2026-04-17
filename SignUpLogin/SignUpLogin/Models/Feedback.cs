using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace SignUpLogin.Models
{
    public class Feedback
    {
        [Key]
        public int Id { get; set; }

        [Required]
        public string StudentIdNumber { get; set; } = string.Empty;

        public int SitInRecordId { get; set; }

        [Required]
        [Range(1, 5)]
        public int Rating { get; set; }          // 1–5 stars

        [StringLength(500)]
        public string? Comment { get; set; }

        public DateTime SubmittedAt { get; set; } = DateTime.Now;

        [ForeignKey(nameof(StudentIdNumber))]
        public Signup? Student { get; set; }

        [ForeignKey(nameof(SitInRecordId))]
        public SitInRecord? SitInRecord { get; set; }
    }
}
