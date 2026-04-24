using Microsoft.EntityFrameworkCore.Migrations;
#nullable disable
namespace SignUpLogin.Migrations
{
    /// <inheritdoc />
    public partial class Add : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            // 1. Drop the foreign key first (correct name from DB)
            migrationBuilder.DropForeignKey(
                name: "FK_StudentPoints_Signups_StudentIdNumber",
                table: "StudentPoints");

            // 2. Drop the old non-unique index
            migrationBuilder.DropIndex(
                name: "IX_StudentPoints_StudentIdNumber",
                table: "StudentPoints");

            // 3. Add new column
            migrationBuilder.AddColumn<string>(
                name: "SignupIdNumber",
                table: "SitInRecords",
                type: "varchar(255)",
                nullable: true)
                .Annotation("MySql:CharSet", "utf8mb4");

            // 4. Recreate the index as unique
            migrationBuilder.CreateIndex(
                name: "IX_StudentPoints_StudentIdNumber",
                table: "StudentPoints",
                column: "StudentIdNumber",
                unique: true);

            // 5. Re-add the foreign key
            migrationBuilder.AddForeignKey(
                name: "FK_StudentPoints_Signups_StudentIdNumber",
                table: "StudentPoints",
                column: "StudentIdNumber",
                principalTable: "Signups",
                principalColumn: "IdNumber",
                onDelete: ReferentialAction.Cascade);

            // 6. Add new index and foreign key for SitInRecords
            migrationBuilder.CreateIndex(
                name: "IX_SitInRecords_SignupIdNumber",
                table: "SitInRecords",
                column: "SignupIdNumber");

            migrationBuilder.AddForeignKey(
                name: "FK_SitInRecords_Signups_SignupIdNumber",
                table: "SitInRecords",
                column: "SignupIdNumber",
                principalTable: "Signups",
                principalColumn: "IdNumber");
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            // 1. Drop new FK for SitInRecords
            migrationBuilder.DropForeignKey(
                name: "FK_SitInRecords_Signups_SignupIdNumber",
                table: "SitInRecords");

            // 2. Drop FK on StudentPoints before touching its index
            migrationBuilder.DropForeignKey(
                name: "FK_StudentPoints_Signups_StudentIdNumber",
                table: "StudentPoints");

            // 3. Drop the unique index
            migrationBuilder.DropIndex(
                name: "IX_StudentPoints_StudentIdNumber",
                table: "StudentPoints");

            migrationBuilder.DropIndex(
                name: "IX_SitInRecords_SignupIdNumber",
                table: "SitInRecords");

            migrationBuilder.DropColumn(
                name: "SignupIdNumber",
                table: "SitInRecords");

            // 4. Recreate the original non-unique index
            migrationBuilder.CreateIndex(
                name: "IX_StudentPoints_StudentIdNumber",
                table: "StudentPoints",
                column: "StudentIdNumber");

            // 5. Re-add the original foreign key
            migrationBuilder.AddForeignKey(
                name: "FK_StudentPoints_Signups_StudentIdNumber",
                table: "StudentPoints",
                column: "StudentIdNumber",
                principalTable: "Signups",
                principalColumn: "IdNumber",
                onDelete: ReferentialAction.Cascade);
        }
    }
}