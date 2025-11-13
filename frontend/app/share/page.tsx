"use client"

import { useEffect, useState } from "react"
import { useRouter } from "next/navigation"
import { SharesList } from "@/components/shares-list"
import { NotificationsPanel } from "@/components/notifications-panel"

export default function SharePage() {
  const router = useRouter()
  const [userId, setUserId] = useState<string | null>(null)

  useEffect(() => {
    const id = localStorage.getItem("userId")
    if (!id) {
      router.push("/login")
    } else {
      setUserId(id)
    }
  }, [router])

  if (!userId) return null

  return (
    <div className="min-h-screen bg-background">
      <div className="container py-8">
        <div className="space-y-8">
          <SharesList userId={userId} />
          <NotificationsPanel userId={userId} />
        </div>
      </div>
    </div>
  )
}
