"use client"

import { useState, useRef, useEffect, forwardRef, useImperativeHandle } from "react"
import { Button } from "./ui/button"

export interface UploadAreaProps {
  userId: string
  onUploadSuccess: () => void
}

export interface UploadAreaRef {
  triggerFileInput: (files: FileList | null) => void
}

export const UploadArea = forwardRef<UploadAreaRef, UploadAreaProps>(({ userId, onUploadSuccess }, ref) => {
  const [isDragging, setIsDragging] = useState(false)
  const [uploading, setUploading] = useState(false)
  const fileInputRef = useRef<HTMLInputElement>(null)

  // Handle file input change
  const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files
    if (files && files.length > 0) {
      await processFiles(files)
    }
    if (fileInputRef.current) {
      fileInputRef.current.value = ''
    }
  }

  // Process the uploaded filesX
  const processFiles = async (files: FileList) => {
    setUploading(true)

    try {
      const token = localStorage.getItem("token")
      const userId = localStorage.getItem("userId")

      if (!token || !userId) {
        throw new Error("Authentication required. Please log in again.")
      }

      const filesArray = Array.from(files)

      const api = process.env.NEXT_PUBLIC_API_URL;
      for (const file of filesArray) {
        const formData = new FormData()
        formData.append("file", file)
        formData.append("user_id", userId)
        const response = await fetch(api + "/api/media-files", {
          method: "POST",
          headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`
          },
          body: formData,
        })

        if (!response.ok) {
          const errorData = await response.json().catch(() => ({}))
          console.error(`Upload error for ${file.name}:`, {
            status: response.status,
            statusText: response.statusText,
            data: errorData
          });
          throw new Error(errorData.message || `Failed to upload ${file.name} (${response.status})`)
        }

        const result = await response.json()
        console.log('Upload successful:', result)
      }

      onUploadSuccess()
    } catch (error: any) {
      console.error("Upload failed:", error)
      alert(`Upload failed: ${error.message || 'Unknown error occurred'}`)
    } finally {
      setUploading(false)
    }
  }

  // Handle drag and drop
  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    if (!isDragging) setIsDragging(true)
  }

  const handleDragLeave = (e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    setIsDragging(false)
  }

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    setIsDragging(false)

    const files = e.dataTransfer.files
    if (files.length > 0) {
      processFiles(files)
    }
  }
  // Add event listeners for drag and drop
  useEffect(() => {
    const handleWindowDragOver = (e: DragEvent) => {
      e.preventDefault()
    }

    const handleWindowDrop = (e: DragEvent) => {
      e.preventDefault()
    }

    window.addEventListener('dragover', handleWindowDragOver)
    window.addEventListener('drop', handleWindowDrop)

    return () => {
      window.removeEventListener('dragover', handleWindowDragOver)
      window.removeEventListener('drop', handleWindowDrop)
    }
  }, [])

  // Expose the triggerFileInput method via ref
  useImperativeHandle(ref, () => ({
    triggerFileInput: (files) => {
      if (files && files.length > 0) {
        processFiles(files)
      }
    }
  }))

  return (
    <div
      onDragOver={handleDragOver}
      onDragLeave={handleDragLeave}
      onDrop={handleDrop}
      className={`border-2 border-dashed rounded-lg p-8 text-center transition ${isDragging ? "border-primary bg-primary/5" : "border-border"}`}>

      <input
        ref={fileInputRef}
        type="file"
        multiple
        onChange={handleFileChange}
        className="hidden"
        accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.txt"
      />

      <label htmlFor="file-input" className="cursor-pointer block text-center">
        <p className="font-medium mb-2">Drag and drop files here</p>
        <p className="text-sm text-muted mb-4 text-center">or</p>
        <Button
          type="button"
          disabled={uploading}
          onClick={() => fileInputRef.current?.click()}
          className="px-4 py-2 bg-primary rounded hover:opacity-90 transition disabled:opacity-50"
        >
          {uploading ? "Uploading..." : "Choose files to upload"}
        </Button>
      </label>
    </div>
  )
})
