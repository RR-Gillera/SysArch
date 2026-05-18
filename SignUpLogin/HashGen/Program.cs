using System;
using BCrypt.Net;

class Program {
    static void Main() {
        string hashedPassword = BCrypt.Net.BCrypt.HashPassword("Admin123!");
        Console.WriteLine(hashedPassword);
    }
}
