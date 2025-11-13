"use client"

import { useRouter } from "next/navigation"
import { useEffect, useState } from "react"
import { AlbumList } from "@/components/album-list"

export default function AlbumsPage() {
  const [userId, setUserId] = useState<string | null>(null)
  const router = useRouter()

  useEffect(() => {
    const id = localStorage.getItem("userId")
    if (!id) {
      router.push("/login")
    } else {
      setUserId(id)
    }
  }, [router])

  if (!userId) return null;

  return (
    <div className="p-8">
      <h1 className="text-3xl font-bold mb-8">Album</h1>
      <AlbumList userId={userId} />
    </div>
  )
}
