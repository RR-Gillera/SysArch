namespace SignUpLogin.Models.ViewModels
{
    public class LeaderboardEntryViewModel
    {
        public string StudentIdNumber { get; set; } = string.Empty;
        public string StudentName { get; set; } = string.Empty;
        public int Points { get; set; }
        public int LifetimePoints { get; set; }
        public int TotalMinutes { get; set; }
    }
}