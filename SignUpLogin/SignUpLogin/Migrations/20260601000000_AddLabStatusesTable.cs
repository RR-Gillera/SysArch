using Microsoft.EntityFrameworkCore.Infrastructure;
using Microsoft.EntityFrameworkCore.Migrations;
using SignUpLogin.Data;

#nullable disable

namespace SignUpLogin.Migrations
{
    [DbContext(typeof(ApplicationDbContext))]
    [Migration("20260601000000_AddLabStatusesTable")]
    public partial class AddLabStatusesTable : Migration
    {
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            // No-op: LabStatuses table is already created by migration
            // 20260410034227_AddLabStatus. This migration is kept only to
            // preserve migration ordering/history.
        }

        protected override void Down(MigrationBuilder migrationBuilder)
        {
            // No-op.
        }
    }
}
