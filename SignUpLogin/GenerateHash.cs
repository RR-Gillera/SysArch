using System;
using System.Reflection;
using System.IO;

class Program {
    static void Main() {
        try {
            // Load BCrypt assembly
            var dllPath = @"z:\TT518\SysArch\SignUpLogin\SignUpLogin\bin\Debug\net10.0\BCrypt.Net-Next.dll";
            var asm = Assembly.LoadFrom(dllPath);
            var bcryptType = asm.GetType("BCrypt.Net.BCrypt");
            var hashMethod = bcryptType.GetMethod("HashPassword", new[] { typeof(string) });
            
            if (hashMethod != null) {
                var hashedPassword = (string)hashMethod.Invoke(null, new object[] { "Admin123!" });
                Console.WriteLine(hashedPassword);
            } else {
                Console.WriteLine("Hash method not found");
            }
        } catch (Exception ex) {
            Console.WriteLine($"Error: {ex.Message}");
        }
    }
}
