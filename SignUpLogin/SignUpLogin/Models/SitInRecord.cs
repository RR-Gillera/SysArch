using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace SignUpLogin.Models
{
    public class SitInRecord
    {
        [Key]
        public int Id { get; set; }

        [Required]
        public string StudentIdNumber { get; set; } = string.Empty;

        [Required]
        [StringLength(120)]
        public string Purpose { get; set; } = string.Empty;

        [Required]
        [StringLength(50)]
        public string Laboratory { get; set; } = string.Empty;

        [StringLength(10)]
        public string? PcNumber { get; set; }

        public DateTime TimeIn { get; set; }

        public DateTime? TimeOut { get; set; }

        [ForeignKey(nameof(StudentIdNumber))]
        public Signup? Student { get; set; }
    }
}
