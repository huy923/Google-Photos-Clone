"use client"

import { useEffect, useState } from "react"
import { useRouter } from "next/navigation"
import { ProfileForm } from "@/components/profile-form"

export default function ProfilePage() {
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
      <h1 className="text-3xl font-bold mb-8">Profile</h1>
      <ProfileForm userId={userId} />
    </div>
  )
}

