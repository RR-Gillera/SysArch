using SignUpLogin.Models;

namespace SignUpLogin.Models.ViewModels
{
    public class AdminDashboardViewModel
    {
        public string UserName { get; set; } = string.Empty;
        public int StudentsRegistered { get; set; }
        public int CurrentlySitIn { get; set; }
        public int TotalSitInRecords { get; set; }
        public List<SitInRecord> CurrentSitIns { get; set; } = new();
        public List<Announcement> Announcements { get; set; } = new();

    }
}
