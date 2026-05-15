using System;
using System.Threading.Tasks;
using Microsoft.EntityFrameworkCore;
using SignUpLogin.Data;
using SignUpLogin.Models;

class Program {
    static async Task Main() {
        var options = new DbContextOptionsBuilder<ApplicationDbContext>()
            .UseMySql("Server=localhost;Database=SignUpLoginDB;User=root;Password=;",
                new MySqlServerVersion(new Version(8, 0, 31)))
            .Options;

        using (var context = new ApplicationDbContext(options)) {
            var admin = await context.Signups.FirstOrDefaultAsync(s => s.IdNumber == "00000001");
            if (admin != null) {
                Console.WriteLine($"Admin Account Found!");
                Console.WriteLine($"ID: {admin.IdNumber}");
                Console.WriteLine($"Name: {admin.FirstName} {admin.LastName}");
                Console.WriteLine($"Role: {admin.Role}");
                Console.WriteLine($"Email: {admin.Email}");
            } else {
                Console.WriteLine("Admin account not found");
            }
        }
    }
}
