using Microsoft.EntityFrameworkCore.Migrations;

#nullable disable

namespace SignUpLogin.Migrations
{
    /// <inheritdoc />
    public partial class AddTotalPCsColumn : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            // Column already added by 20260518150321_AddTotalPCsToLabStatus (manual fix).
            // Up() intentionally left empty to avoid duplicate column error.
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
