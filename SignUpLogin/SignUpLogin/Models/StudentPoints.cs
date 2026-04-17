using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace SignUpLogin.Models
{
    public class StudentPoints
    {
        [Key]
        public int Id { get; set; }

        [Required]
        public string StudentIdNumber { get; set; } = string.Empty;

        public int Points { get; set; } = 0;

        [StringLength(200)]
        public string? LastRewardReason { get; set; }

        public DateTime UpdatedAt { get; set; } = DateTime.Now;

        [ForeignKey(nameof(StudentIdNumber))]
        public Signup? Student { get; set; }
    }
}
