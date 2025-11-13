"use client"

import { useEffect, useState, useRef } from "react"
import { useRouter } from "next/navigation"
import { GalleryGrid } from "@/components/gallery-grid"
import DropUploadArea from "@/components/drop-upload-area"
import { Button } from "@/components/ui/button"

export default function Home() {
  const router = useRouter()
  const [userId, setUserId] = useState<string | null>(null)
  const [uploading, setUploading] = useState(false)
  const fileInputRef = useRef<HTMLInputElement | null>(null)
  const [refreshFlag, setRefreshFlag] = useState(0)

  useEffect(() => {
    const id = localStorage.getItem("userId")
    if (!id) {
      router.push("/login")
    } else {
      setUserId(id)
    }
  }, [router])

  const handleFolderChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files
    if (!files || files.length === 0 || !userId) return
    setUploading(true)
    const token = localStorage.getItem("token")
    try {
      const api = process.env.NEXT_PUBLIC_API_URL;
      for (const file of Array.from(files)) {
        const formData = new FormData()
        formData.append("file", file)
        formData.append("user_id", userId)
        await fetch(api + "/api/media-files", {
          method: "POST",
          headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`
          },
          body: formData,
        })
      }
      setRefreshFlag(prev => prev + 1)
    } catch (err) {
      alert("Upload folder failed!")
    } finally {
      setUploading(false)
      if (fileInputRef.current) fileInputRef.current.value = ""
    }
  }

  if (!userId) return null

  return (
    <div className="w-full mt-2" style={{ position: 'relative', minHeight: '80vh' }}>
      <DropUploadArea userId={userId} className="" onUploadSuccess={() => setRefreshFlag(prev => prev + 1)} />
      <GalleryGrid userId={userId} key={refreshFlag} className="p-4" />
    </div>
  )
}