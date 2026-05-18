using Microsoft.EntityFrameworkCore.Migrations;

#nullable disable

namespace SignUpLogin.Migrations
{
    /// <inheritdoc />
    public partial class AddTotalPCsToLabStatus : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.AddColumn<int>(
                name: "TotalPCs",
                table: "LabStatuses",
                type: "int",
                nullable: false,
                defaultValue: 0);
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropColumn(
                name: "TotalPCs",
                table: "LabStatuses");
        }
    }
}
