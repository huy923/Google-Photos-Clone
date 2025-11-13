"use client"

import type React from "react"

import { useState, useEffect } from "react"
import { apiClient } from "@/lib/api-client"

interface Share {
  id: number
  share_token: string
  shareable_type: string
  shareable_id: number
  permission: string
  access_type: string
  expires_at: string
  view_count: number
  is_active: boolean
}

export function SharesList({ userId }: { userId: string }) {
  const [shares, setShares] = useState<Share[]>([])
  const [loading, setLoading] = useState(true)
  const [newShare, setNewShare] = useState({
    shareable_type: "media_file",
    shareable_id: "",
    permission: "view",
    access_type: "public",
  })

  useEffect(() => {
    fetchShares()
  }, [userId])

  const fetchShares = async () => {
    try {
      const data = await apiClient.get(`/shares?user_id=${userId}`)
      setShares(Array.isArray(data) ? data : data.data || [])
    } catch (error) {
      console.error("Failed to fetch shares:", error)
    } finally {
      setLoading(false)
    }
  }

  const handleCreateShare = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!newShare.shareable_id) return

    try {
      await apiClient.post("/shares", {
        user_id: userId,
        ...newShare,
        shareable_id: Number.parseInt(newShare.shareable_id),
      })
      setNewShare({
        shareable_type: "media_file",
        shareable_id: "",
        permission: "view",
        access_type: "public",
      })
      await fetchShares()
    } catch (error) {
      console.error("Failed to create share:", error)
    }
  }

  const handleDeleteShare = async (id: number) => {
    if (!confirm("Bạn chắc chắn muốn xóa chia sẻ này?")) return

    try {
      await apiClient.delete(`/shares/${id}`)
      await fetchShares()
    } catch (error) {
      console.error("Failed to delete share:", error)
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
      <h2 className="text-2xl font-bold mb-6">Chia sẻ</h2>

      <form onSubmit={handleCreateShare} className="mb-6 space-y-4 p-4 border border-border rounded-lg">
        <div>
          <label className="block text-sm font-medium mb-2">Loại chia sẻ</label>
          <select
            value={newShare.shareable_type}
            onChange={(e) => setNewShare({ ...newShare, shareable_type: e.target.value })}
            className="w-full px-4 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
          >
            <option value="media_file">Ảnh/Video</option>
            <option value="album">Album</option>
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium mb-2">ID</label>
          <input
            type="number"
            value={newShare.shareable_id}
            onChange={(e) => setNewShare({ ...newShare, shareable_id: e.target.value })}
            placeholder="Nhập ID"
            className="w-full px-4 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
          />
        </div>

        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium mb-2">Quyền</label>
            <select
              value={newShare.permission}
              onChange={(e) => setNewShare({ ...newShare, permission: e.target.value })}
              className="w-full px-4 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option value="view">Xem</option>
              <option value="download">Tải xuống</option>
              <option value="comment">Bình luận</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium mb-2">Loại truy cập</label>
            <select
              value={newShare.access_type}
              onChange={(e) => setNewShare({ ...newShare, access_type: e.target.value })}
              className="w-full px-4 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option value="public">Công khai</option>
              <option value="friends">Bạn bè</option>
              <option value="specific">Cụ thể</option>
            </select>
          </div>
        </div>

        <button type="submit" className="w-full px-4 py-2 bg-primary text-white rounded-lg hover:opacity-90 transition">
          Tạo chia sẻ
        </button>
      </form>

      {shares.length === 0 ? (
        <p className="text-muted">Chưa có chia sẻ nào</p>
      ) : (
        <div className="space-y-3">
          {shares.map((share) => (
            <div key={share.id} className="p-4 border border-border rounded-lg">
              <div className="flex justify-between items-start mb-2">
                <div>
                  <p className="font-medium">
                    {share.shareable_type === "media_file" ? "Ảnh/Video" : "Album"} #{share.shareable_id}
                  </p>
                  <p className="text-sm text-muted">
                    Token: <code className="bg-background px-2 py-1 rounded">{share.share_token}</code>
                  </p>
                </div>
                <button
                  onClick={() => handleDeleteShare(share.id)}
                  className="text-sm text-destructive hover:underline"
                >
                  Xóa
                </button>
              </div>
              <div className="text-xs text-muted space-y-1">
                <p>Quyền: {share.permission}</p>
                <p>Loại: {share.access_type}</p>
                <p>Lượt xem: {share.view_count}</p>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
