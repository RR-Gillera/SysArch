#!/usr/bin/env dotnet-script
// Simple script to generate BCrypt hash for admin password

#r "nuget: BCrypt.Net-Next, 4.0.3"

using BCrypt.Net;

string password = "Admin123!";
string hashedPassword = BCrypt.Net.BCrypt.HashPassword(password);
Console.WriteLine(hashedPassword);
