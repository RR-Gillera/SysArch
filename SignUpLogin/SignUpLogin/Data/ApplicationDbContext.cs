using Microsoft.EntityFrameworkCore;
using SignUpLogin.Models;

namespace SignUpLogin.Data
{
    public class ApplicationDbContext : DbContext
    {
        public ApplicationDbContext(DbContextOptions<ApplicationDbContext> options) : base(options)
        {
        }

        public DbSet<Signup> Signups { get; set; } = null!;

        protected override void OnModelCreating(ModelBuilder modelBuilder)
        {
            base.OnModelCreating(modelBuilder);

            modelBuilder.Entity<Signup>()
                .HasKey(s => s.IdNumber);

            modelBuilder.Entity<Signup>()
                .HasIndex(s => s.IdNumber)
                .IsUnique();

            modelBuilder.Entity<Signup>()
                .HasIndex(s => s.Email)
                .IsUnique();
        }
    }
}