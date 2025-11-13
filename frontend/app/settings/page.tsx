"use client"

import { useEffect, useState } from "react"
import { useRouter } from "next/navigation"

export default function SettingsPage() {
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
    <div className="p-8">
      <h1 className="text-3xl font-bold mb-8">Settings</h1>
      <p>This is the settings page. More content will be added soon.</p>
    </div>
  )
}
