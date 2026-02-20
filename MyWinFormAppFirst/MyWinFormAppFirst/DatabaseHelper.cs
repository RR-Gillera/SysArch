using Microsoft.Data.Sqlite;
using System.Data;

namespace MyWinFormAppFirst
{
    public class DatabaseHelper
    {
        private readonly string connectionString;

        public DatabaseHelper()
        {
            string dbPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "students.db");
            connectionString = $"Data Source={dbPath}";
            InitializeDatabase();
        }

        private void InitializeDatabase()
        {
            using (var connection = new SqliteConnection(connectionString))
            {
                connection.Open();
                string createTableQuery = @"
                    CREATE TABLE IF NOT EXISTS Students (
                        Id INTEGER PRIMARY KEY AUTOINCREMENT,
                        Name TEXT NOT NULL,
                        Age INTEGER NOT NULL,
                        Email TEXT NOT NULL
                    )";

                using (var command = new SqliteCommand(createTableQuery, connection))
                {
                    command.ExecuteNonQuery();
                }
            }
        }

        public DataTable GetAllStudents()
        {
            var dataTable = new DataTable();
            using (var connection = new SqliteConnection(connectionString))
            {
                connection.Open();
                string query = "SELECT * FROM Students";
                using (var command = new SqliteCommand(query, connection))
                {
                    using (var reader = command.ExecuteReader())
                    {
                        dataTable.Load(reader);
                    }
                }
            }
            return dataTable;
        }

        public void AddStudent(string name, int age, string email)
        {
            using (var connection = new SqliteConnection(connectionString))
            {
                connection.Open();
                string query = "INSERT INTO Students (Name, Age, Email) VALUES (@Name, @Age, @Email)";
                using (var command = new SqliteCommand(query, connection))
                {
                    command.Parameters.AddWithValue("@Name", name);
                    command.Parameters.AddWithValue("@Age", age);
                    command.Parameters.AddWithValue("@Email", email);
                    command.ExecuteNonQuery();
                }
            }
        }

        public void UpdateStudent(int id, string name, int age, string email)
        {
            using (var connection = new SqliteConnection(connectionString))
            {
                connection.Open();
                string query = "UPDATE Students SET Name = @Name, Age = @Age, Email = @Email WHERE Id = @Id";
                using (var command = new SqliteCommand(query, connection))
                {
                    command.Parameters.AddWithValue("@Id", id);
                    command.Parameters.AddWithValue("@Name", name);
                    command.Parameters.AddWithValue("@Age", age);
                    command.Parameters.AddWithValue("@Email", email);
                    command.ExecuteNonQuery();
                }
            }
        }

        public void DeleteStudent(int id)
        {
            using (var connection = new SqliteConnection(connectionString))
            {
                connection.Open();
                string query = "DELETE FROM Students WHERE Id = @Id";
                using (var command = new SqliteCommand(query, connection))
                {
                    command.Parameters.AddWithValue("@Id", id);
                    command.ExecuteNonQuery();
                }
            }
        }
    }
}
