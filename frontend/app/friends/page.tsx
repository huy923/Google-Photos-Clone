"use client"

import { useEffect, useState } from "react"
import { useRouter } from "next/navigation"
import { FriendsList } from "@/components/friends-list"

export default function FriendsPage() {
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
      <h1 className="text-3xl font-bold mb-8">Friends</h1>
      <FriendsList userId={userId} />
    </div>
  )
}
