"use client"

import type React from "react"

import { useState, useEffect } from "react"
import { apiClient } from "@/lib/api-client"
import { Input } from "./ui/input"

interface Friendship {
  id: number
  friend_id: number
  status: string
}

interface User {
  id: number
  name: string
  email: string
}

export function FriendsList({ userId }: { userId: string }) {
  const [friends, setFriends] = useState<any[]>([])
  const [loading, setLoading] = useState(true)
  const [searchEmail, setSearchEmail] = useState("")

  useEffect(() => {
    fetchFriends()
  }, [userId])

  const fetchFriends = async () => {
    try {
      const data = await apiClient.get(`/friendships?user_id=${userId}`)
      setFriends(Array.isArray(data) ? data : data.data || [])
    } catch (error) {
      console.error("Failed to fetch friends:", error)
    } finally {
      setLoading(false)
    }
  }

  const handleAddFriend = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!searchEmail.trim()) return

    try {
      const users = await apiClient.get(`/users?email=${searchEmail}`)
      const user = Array.isArray(users) ? users[0] : users.data?.[0]

      if (user) {
        await apiClient.post("/friendships", {
          user_id: userId,
          friend_id: user.id,
          status: "pending",
        })
        setSearchEmail("")
        await fetchFriends()
      }
    } catch (error) {
      console.error("Failed to add friend:", error)
    }
  }

  const handleRemoveFriend = async (friendshipId: number) => {
    if (!confirm("Are you sure to remove this friend?")) return

    try {
      await apiClient.delete(`/friendships/${friendshipId}`)
      await fetchFriends()
    } catch (error) {
      console.error("Failed to remove friend:", error)
    }
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>
    )
  }

  return (
    <div className="bg-card border border-border rounded-lg p-6 max-w-2xl">
      <h2 className="text-2xl font-bold mb-6">Friends</h2>

      <form onSubmit={handleAddFriend} className="mb-6 flex gap-2">
        <Input
          type="email"
          value={searchEmail}
          onChange={(e) => setSearchEmail(e.target.value)}
          placeholder="Enter friend's email"
          className="flex-1 px-4 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
        />
        <button type="submit" className="px-4 py-2 bg-primary text-white rounded-lg hover:opacity-90 transition">
          Add
        </button>
      </form>

      {friends.length === 0 ? (
        <p className="">You right now have no friends</p>
      ) : (
        <div className="space-y-2">
          {friends.map((friendship) => (
            <div key={friendship.id} className="flex items-center justify-between p-3 border border-border rounded-lg">
              <div>
                <p className="font-medium">Bạn #{friendship.friend_id}</p>
                <p className="text-sm text-muted capitalize">{friendship.status}</p>
              </div>
              <button
                onClick={() => handleRemoveFriend(friendship.id)}
                className="text-sm text-destructive hover:underline"
              >
                Remove
              </button>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
