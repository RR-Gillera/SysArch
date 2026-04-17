namespace SignUpLogin.Models.ViewModels
{
    public class StudentHomeViewModel
    {
        public string UserName { get; set; } = string.Empty;
        public string IdNumber { get; set; } = string.Empty;
        public string Course { get; set; } = string.Empty;
        public string CourseLevel { get; set; } = string.Empty;
        public string Email { get; set; } = string.Empty;
        public string? ProfileImagePath { get; set; }
        public int RemainingSessions { get; set; }
        public int UnreadAnnouncementsCount { get; set; }
        public List<Announcement> Announcements { get; set; } = new();
        public List<SitInRecord> RecentSitInHistory { get; set; } = new();
        public bool IsCurrentlyActive { get; set; }
        public SitInRecord? ActiveSitIn { get; set; }
        public int Points { get; set; }
        public string? LastRewardReason { get; set; }
        public List<Feedback> SubmittedFeedbacks { get; set; } = new();

        // Lab statuses set by admin (Available, Class is in session, Maintenance, Unavailable)
        public List<LabStatus> LabStatuses { get; set; } = new();
    }
}