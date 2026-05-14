using Microsoft.EntityFrameworkCore.Migrations;

#nullable disable

namespace SignUpLogin.Migrations
{
    /// <inheritdoc />
    public partial class AddLifetimePoints : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.AddColumn<int>(
                name: "LifetimePoints",
                table: "StudentPoints",
                type: "int",
                nullable: false,
                defaultValue: 0);
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropColumn(
                name: "LifetimePoints",
                table: "StudentPoints");
        }
    }
}
