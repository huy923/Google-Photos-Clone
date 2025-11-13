"use client";
import { useEffect, useState } from "react";
import { apiClient } from "@/lib/api-client";

export default function UsersPage() {
  const [users, setUsers] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function fetchUsers() {
      try {
        const result = await apiClient.get("/users");
        setUsers(Array.isArray(result) ? result : result.data || []);
      } catch (err) {
        setUsers([]);
      } finally {
        setLoading(false);
      }
    }
    fetchUsers();
  }, []);

  if (loading) return <div className="p-8">All users...</div>;

  return (
    <div className="p-8">
      <h1 className="text-3xl font-bold mb-8">All users</h1>
      <table className="w-full border border-border rounded-lg">
        <thead>
          <tr className="bg-muted">
            <th className="p-2 text-left">ID</th>
            <th className="p-2 text-left">Name</th>
            <th className="p-2 text-left">Email</th>
            <th className="p-2 text-left">Status</th>
          </tr>
        </thead>
        <tbody>
          {users.map(user => (
            <tr key={user.id} className="border-t">
              <td className="p-2">{user.id}</td>
              <td className="p-2">{user.name}</td>
              <td className="p-2">{user.email}</td>
              <td className={`p-2 font-semibold ${user.is_active ? "text-green-600" : "text-red-600"}`}>{user.is_active ? "is active" : "disabled"}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
