"use client"

import type React from "react"

import { useState, useEffect } from "react"
import { apiClient } from "@/lib/api-client"

interface Profile {
  id: number
  user_id: number
  first_name: string
  last_name: string
  bio: string
  phone: string
  location: string
}

export function ProfileForm({ userId }: { userId: string }) {
  const [profile, setProfile] = useState<Profile | null>(null)
  const [loading, setLoading] = useState(true)
  const [editing, setEditing] = useState(false)
  const [formData, setFormData] = useState<Partial<Profile>>({})

  useEffect(() => {
    fetchProfile()
  }, [userId])

  const fetchProfile = async () => {
    try {
      const data = await apiClient.get(`/profiles?user_id=${userId}`)
      const profileData = Array.isArray(data) ? data[0] : data.data?.[0]
      setProfile(profileData)
      setFormData(profileData || {})
    } catch (error) {
      console.error("Failed to fetch profile:", error)
    } finally {
      setLoading(false)
    }
  }

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    })
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    try {
      if (profile?.id) {
        await apiClient.put(`/profiles/${profile.id}`, formData)
      } else {
        await apiClient.post("/profiles", {
          user_id: userId,
          ...formData,
        })
      }
      await fetchProfile()
      setEditing(false)
    } catch (error) {
      console.error("Failed to save profile:", error)
    }
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>
    )
  }

  if (editing) {
    return (
      <form onSubmit={handleSubmit} className="bg-card border border-border rounded-lg p-6 max-w-2xl">
        <div className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium mb-2">Tên</label>
              <input
                type="text"
                name="first_name"
                value={formData.first_name || ""}
                onChange={handleChange}
                className="w-full px-4 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-2">Họ</label>
              <input
                type="text"
                name="last_name"
                value={formData.last_name || ""}
                onChange={handleChange}
                className="w-full px-4 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium mb-2">Điện thoại</label>
            <input
              type="tel"
              name="phone"
              value={formData.phone || ""}
              onChange={handleChange}
              className="w-full px-4 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-2">Địa điểm</label>
            <input
              type="text"
              name="location"
              value={formData.location || ""}
              onChange={handleChange}
              className="w-full px-4 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-2">Tiểu sử</label>
            <textarea
              name="bio"
              value={formData.bio || ""}
              onChange={handleChange}
              rows={4}
              className="w-full px-4 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
            />
          </div>

          <div className="flex gap-2">
            <button type="submit" className="px-4 py-2 bg-primary text-white rounded-lg hover:opacity-90 transition">
              Lưu
            </button>
            <button
              type="button"
              onClick={() => setEditing(false)}
              className="px-4 py-2 border border-border rounded-lg hover:bg-card transition"
            >
              Hủy
            </button>
          </div>
        </div>
      </form>
    )
  }

  return (
    <div className="bg-card border border-border rounded-lg p-6 max-w-2xl">
      <div className="flex justify-between items-start mb-6">
        <h2 className="text-2xl font-bold">Hồ sơ cá nhân</h2>
        <button
          onClick={() => setEditing(true)}
          className="px-4 py-2 bg-primary text-white rounded-lg hover:opacity-90 transition"
        >
          Chỉnh sửa
        </button>
      </div>

      <div className="space-y-4">
        <div>
          <p className="text-sm text-muted">Tên</p>
          <p className="font-medium">{profile?.first_name || "Chưa cập nhật"}</p>
        </div>
        <div>
          <p className="text-sm text-muted">Họ</p>
          <p className="font-medium">{profile?.last_name || "Chưa cập nhật"}</p>
        </div>
        <div>
          <p className="text-sm text-muted">Điện thoại</p>
          <p className="font-medium">{profile?.phone || "Chưa cập nhật"}</p>
        </div>
        <div>
          <p className="text-sm text-muted">Địa điểm</p>
          <p className="font-medium">{profile?.location || "Chưa cập nhật"}</p>
        </div>
        <div>
          <p className="text-sm text-muted">Tiểu sử</p>
          <p className="font-medium">{profile?.bio || "Chưa cập nhật"}</p>
        </div>
      </div>
    </div>
  )
}
