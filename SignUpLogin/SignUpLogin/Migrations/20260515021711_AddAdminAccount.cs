using Microsoft.EntityFrameworkCore.Migrations;

#nullable disable

namespace SignUpLogin.Migrations
{
    /// <inheritdoc />
    public partial class AddAdminAccount : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            // BCrypt hash of "Admin123!"
            string hashedPassword = "$2a$11$GSRtPLysANrvJ80N/Bvlj.lE0sGM9C88Bdy7rX5dhNUqaUkiSVfLK";
            
            migrationBuilder.Sql($@"
                INSERT INTO Signups (IdNumber, FirstName, LastName, MiddleName, CourseLevel, Password, Email, Course, Address, Role, RemainingSessions, CreatedAt, LastAnnouncementsReadAt, ProfileImagePath)
                VALUES ('00000001', 'System', 'Admin', '', 'Administrator', '{hashedPassword}', 'admin@system.local', 'Administration', 'System', 'Admin', 30, NOW(), NULL, NULL);
            ");
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.Sql("DELETE FROM Signups WHERE IdNumber = '00000001';");
        }
    }
}
