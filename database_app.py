import sqlite3
from datetime import datetime

class DatabaseApp:
    def __init__(self, db_name='app.db'):
        """Initialize database connection"""
        self.conn = sqlite3.connect(db_name)
        self.cursor = self.conn.cursor()
        self.create_table()
    
    def create_table(self):
        """Create users table if it doesn't exist"""
        self.cursor.execute('''
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ''')
        self.conn.commit()
    
    def add_user(self, name, email):
        """Add a new user to the database"""
        try:
            self.cursor.execute(
                'INSERT INTO users (name, email) VALUES (?, ?)',
                (name, email)
            )
            self.conn.commit()
            print(f"✓ User '{name}' added successfully")
        except sqlite3.IntegrityError:
            print(f"✗ Error: Email '{email}' already exists")
    
    def get_all_users(self):
        """Retrieve all users from the database"""
        self.cursor.execute('SELECT id, name, email, created_at FROM users')
        return self.cursor.fetchall()
    
    def get_user_by_id(self, user_id):
        """Retrieve a specific user by ID"""
        self.cursor.execute(
            'SELECT id, name, email, created_at FROM users WHERE id = ?',
            (user_id,)
        )
        return self.cursor.fetchone()
    
    def update_user(self, user_id, name=None, email=None):
        """Update a user's information"""
        if name:
            self.cursor.execute(
                'UPDATE users SET name = ? WHERE id = ?',
                (name, user_id)
            )
        if email:
            try:
                self.cursor.execute(
                    'UPDATE users SET email = ? WHERE id = ?',
                    (email, user_id)
                )
            except sqlite3.IntegrityError:
                print(f"✗ Error: Email '{email}' already exists")
                return
        self.conn.commit()
        print(f"✓ User {user_id} updated successfully")
    
    def delete_user(self, user_id):
        """Delete a user from the database"""
        self.cursor.execute('DELETE FROM users WHERE id = ?', (user_id,))
        self.conn.commit()
        print(f"✓ User {user_id} deleted successfully")
    
    def close(self):
        """Close database connection"""
        self.conn.close()
        print("Database connection closed")


def main():
    """Main application"""
    db = DatabaseApp()
    
    # Add users
    print("Adding users...")
    db.add_user("Alice Johnson", "alice@example.com")
    db.add_user("Bob Smith", "bob@example.com")
    db.add_user("Charlie Brown", "charlie@example.com")
    
    # Display all users
    print("\n--- All Users ---")
    users = db.get_all_users()
    for user in users:
        print(f"ID: {user[0]}, Name: {user[1]}, Email: {user[2]}, Created: {user[3]}")
    
    # Get specific user
    print("\n--- Get User by ID ---")
    user = db.get_user_by_id(1)
    if user:
        print(f"User: {user[1]} ({user[2]})")
    
    # Update user
    print("\n--- Update User ---")
    db.update_user(1, name="Alice Updated")
    
    # Display updated user
    print("\n--- Updated User ---")
    user = db.get_user_by_id(1)
    if user:
        print(f"User: {user[1]} ({user[2]})")
    
    # Delete user
    print("\n--- Delete User ---")
    db.delete_user(2)
    
    # Display remaining users
    print("\n--- Final User List ---")
    users = db.get_all_users()
    for user in users:
        print(f"ID: {user[0]}, Name: {user[1]}, Email: {user[2]}")
    
    db.close()


if __name__ == "__main__":
    main()
