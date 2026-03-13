using Microsoft.EntityFrameworkCore.Metadata;
using Microsoft.EntityFrameworkCore.Migrations;

#nullable disable

namespace SignUpLogin.Migrations
{
    /// <inheritdoc />
    public partial class AddRoleAndNewPrimaryKey : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropPrimaryKey(
                name: "PK_Signups",
                table: "Signups");

            migrationBuilder.DropColumn(
                name: "Id",
                table: "Signups");

            migrationBuilder.AlterColumn<string>(
                name: "IdNumber",
                table: "Signups",
                type: "varchar(255)",
                nullable: false,
                oldClrType: typeof(string),
                oldType: "longtext")
                .Annotation("MySql:CharSet", "utf8mb4")
                .OldAnnotation("MySql:CharSet", "utf8mb4");

            migrationBuilder.AlterColumn<string>(
                name: "Email",
                table: "Signups",
                type: "varchar(255)",
                nullable: false,
                oldClrType: typeof(string),
                oldType: "longtext")
                .Annotation("MySql:CharSet", "utf8mb4")
                .OldAnnotation("MySql:CharSet", "utf8mb4");

            migrationBuilder.AddColumn<string>(
                name: "Role",
                table: "Signups",
                type: "longtext",
                nullable: false)
                .Annotation("MySql:CharSet", "utf8mb4");

            migrationBuilder.AddPrimaryKey(
                name: "PK_Signups",
                table: "Signups",
                column: "IdNumber");

            migrationBuilder.CreateIndex(
                name: "IX_Signups_Email",
                table: "Signups",
                column: "Email",
                unique: true);

            migrationBuilder.CreateIndex(
                name: "IX_Signups_IdNumber",
                table: "Signups",
                column: "IdNumber",
                unique: true);
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropPrimaryKey(
                name: "PK_Signups",
                table: "Signups");

            migrationBuilder.DropIndex(
                name: "IX_Signups_Email",
                table: "Signups");

            migrationBuilder.DropIndex(
                name: "IX_Signups_IdNumber",
                table: "Signups");

            migrationBuilder.DropColumn(
                name: "Role",
                table: "Signups");

            migrationBuilder.AlterColumn<string>(
                name: "Email",
                table: "Signups",
                type: "longtext",
                nullable: false,
                oldClrType: typeof(string),
                oldType: "varchar(255)")
                .Annotation("MySql:CharSet", "utf8mb4")
                .OldAnnotation("MySql:CharSet", "utf8mb4");

            migrationBuilder.AlterColumn<string>(
                name: "IdNumber",
                table: "Signups",
                type: "longtext",
                nullable: false,
                oldClrType: typeof(string),
                oldType: "varchar(255)")
                .Annotation("MySql:CharSet", "utf8mb4")
                .OldAnnotation("MySql:CharSet", "utf8mb4");

            migrationBuilder.AddColumn<int>(
                name: "Id",
                table: "Signups",
                type: "int",
                nullable: false,
                defaultValue: 0)
                .Annotation("MySql:ValueGenerationStrategy", MySqlValueGenerationStrategy.IdentityColumn);

            migrationBuilder.AddPrimaryKey(
                name: "PK_Signups",
                table: "Signups",
                column: "Id");
        }
    }
}
