"use client"

import { useState, useEffect } from "react"
import { apiClient } from "@/lib/api-client"

interface Notification {
  id: number
  type: string
  title: string
  message: string
  is_read: boolean
  created_at: string
}

export function NotificationsPanel({ userId }: { userId: string }) {
  const [notifications, setNotifications] = useState<Notification[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetchNotifications()
    const interval = setInterval(fetchNotifications, 30000)
    return () => clearInterval(interval)
  }, [userId])

  const fetchNotifications = async () => {
    try {
      const data = await apiClient.get(`/notifications?user_id=${userId}`)
      setNotifications(Array.isArray(data) ? data : data.data || [])
    } catch (error) {
      console.error("Failed to fetch notifications:", error)
    } finally {
      setLoading(false)
    }
  }

  const handleMarkAsRead = async (id: number) => {
    try {
      await apiClient.put(`/notifications/${id}`, { is_read: true })
      await fetchNotifications()
    } catch (error) {
      console.error("Failed to mark notification as read:", error)
    }
  }

  const handleDelete = async (id: number) => {
    try {
      await apiClient.delete(`/notifications/${id}`)
      await fetchNotifications()
    } catch (error) {
      console.error("Failed to delete notification:", error)
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
      <h2 className="text-2xl font-bold mb-6">Thông báo</h2>

      {notifications.length === 0 ? (
        <p className="text-muted">Không có thông báo nào</p>
      ) : (
        <div className="space-y-3">
          {notifications.map((notif) => (
            <div
              key={notif.id}
              className={`p-4 border rounded-lg ${notif.is_read ? "border-border bg-background" : "border-primary bg-primary/5"}`}
            >
              <div className="flex justify-between items-start mb-2">
                <div>
                  <p className="font-medium">{notif.title}</p>
                  <p className="text-sm text-muted">{notif.message}</p>
                </div>
                <div className="flex gap-2">
                  {!notif.is_read && (
                    <button onClick={() => handleMarkAsRead(notif.id)} className="text-xs text-primary hover:underline">
                      Đánh dấu
                    </button>
                  )}
                  <button onClick={() => handleDelete(notif.id)} className="text-xs text-destructive hover:underline">
                    Xóa
                  </button>
                </div>
              </div>
              <p className="text-xs text-muted">{new Date(notif.created_at).toLocaleString("vi-VN")}</p>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
