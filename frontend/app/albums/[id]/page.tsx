"use client"

import { useState, useEffect, useCallback } from "react"
import { useParams, useRouter } from "next/navigation"
import { apiClient } from "@/lib/api-client"
import { ArrowLeft, Trash2, Plus } from "lucide-react"
import Link from "next/link"
import { GallerySelectModal } from "@/components/gallery-select-modal"

interface MediaFile {
  id: number
  name: string
  file_path: string
  size: number
  mime_type: string
  is_deleted: boolean
  created_at: string
}

interface Album {
  id: number
  name: string
  description: string
  user_id: number
  media_files: MediaFile[]
}

export default function AlbumDetailPage() {
  const params = useParams()
  const router = useRouter()
  const albumId = params.id as string

  const [album, setAlbum] = useState<Album | null>(null)
  const [loading, setLoading] = useState(true)
  const [isDragging, setIsDragging] = useState(false)
  const [selectedFiles, setSelectedFiles] = useState<Set<number>>(new Set())
  const [isAddingMedia, setIsAddingMedia] = useState(false)
  const [showGalleryModal, setShowGalleryModal] = useState(false)
  const [userId, setUserId] = useState<string>("")

  // Fetch album details
  useEffect(() => {
    const fetchAlbum = async () => {
      try {
        const storedUserId = localStorage.getItem("userId")
        if (storedUserId) {
          setUserId(storedUserId)
        }

        const data = await apiClient.get(`/albums/${albumId}`)
        setAlbum(data)
      } catch (error) {
        console.error("Failed to fetch album:", error)
        alert("Failed to load album")
        router.push("/albums")
      } finally {
        setLoading(false)
      }
    }

    if (albumId) {
      fetchAlbum()
    }
  }, [albumId, router])

  // Toggle file selection
  const toggleFileSelection = (fileId: number) => {
    const newSelected = new Set(selectedFiles)
    if (newSelected.has(fileId)) {
      newSelected.delete(fileId)
    } else {
      newSelected.add(fileId)
    }
    setSelectedFiles(newSelected)
  }

  // Select all files
  const handleSelectAll = () => {
    if (!album) return
    if (selectedFiles.size === album.media_files.length) {
      setSelectedFiles(new Set())
    } else {
      setSelectedFiles(new Set(album.media_files.map(f => f.id)))
    }
  }

  // Remove selected files from album
  const handleRemoveFiles = async () => {
    if (selectedFiles.size === 0) {
      alert("Please select files to remove")
      return
    }

    if (!confirm(`Remove ${selectedFiles.size} file(s) from album?`)) return

    try {
      await apiClient.post(`/albums/${albumId}/remove-media`, {
        media_ids: Array.from(selectedFiles)
      })

      if (album) {
        setAlbum({
          ...album,
          media_files: album.media_files.filter(f => !selectedFiles.has(f.id))
        })
      }
      setSelectedFiles(new Set())
      alert("Files removed from album")
    } catch (error) {
      console.error("Failed to remove files:", error)
      alert("Failed to remove files")
    }
  }

  // Handle drag over
  const handleDragOver = useCallback((e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    setIsDragging(true)
  }, [])

  const handleDragLeave = useCallback((e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    setIsDragging(false)
  }, [])

  // Handle drop - add files to album
  const handleDrop = useCallback(async (e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    setIsDragging(false)

    if (!album) return

    // Get media file IDs from drag data or search gallery
    const files = e.dataTransfer.files
    if (files.length > 0) {
      // If files are dragged from file system, show message
      alert("Please use the gallery below to select photos to add to this album")
      return
    }

    // If files are dragged from within the app, extract IDs from dataTransfer
    const mediaIds = e.dataTransfer.getData("mediaIds")
    if (mediaIds) {
      await addFilesToAlbum(JSON.parse(mediaIds))
    }
  }, [album])

  // Add files to album
  const addFilesToAlbum = async (mediaIds: number[]) => {
    if (mediaIds.length === 0) return

    setIsAddingMedia(true)
    try {
      const updatedAlbum = await apiClient.post(`/albums/${albumId}/add-media`, {
        media_ids: mediaIds
      })
      setAlbum(updatedAlbum)
      setSelectedFiles(new Set())
      alert(`Added ${mediaIds.length} file(s) to album`)
    } catch (error) {
      console.error("Failed to add files:", error)
      alert("Failed to add files to album")
    } finally {
      setIsAddingMedia(false)
    }
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>
    )
  }

  if (!album) {
    return (
      <div className="container mx-auto p-6">
        <p className="text-center text-muted-foreground">Album not found</p>
      </div>
    )
  }

  return (
    <div className="container mx-auto p-6">
      {/* Header */}
      <div className="mb-8">
        <Link href="/albums" className="inline-flex items-center gap-2 text-primary hover:opacity-70 transition mb-4">
          <ArrowLeft className="h-4 w-4" />
          Back to Albums
        </Link>

        <h1 className="text-4xl font-bold mb-2">📁 {album.name}</h1>
        <p className="text-muted-foreground mb-4">{album.description}</p>
        <p className="text-sm text-muted-foreground">
          {album.media_files.length} photo{album.media_files.length !== 1 ? "s" : ""}
        </p>
      </div>

      {/* Drop Zone */}
      <div
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        className={`border-2 border-dashed rounded-lg p-8 mb-8 transition ${isDragging
            ? "border-primary bg-primary/5"
            : "border-border"
          }`}
      >
        <div className="text-center">
          <p className="text-lg font-medium mb-2">📸 Add photos to this album</p>
          <p className="text-sm text-muted-foreground mb-4">
            Click the button below or drag photos from your gallery here
          </p>
          <button
            onClick={() => setShowGalleryModal(true)}
            className="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:opacity-90 transition font-medium"
          >
            <Plus className="h-5 w-5" />
            Select Photos
          </button>
        </div>
      </div>

      {/* Gallery Modal */}
      <GallerySelectModal
        albumId={albumId}
        isOpen={showGalleryModal}
        onClose={() => setShowGalleryModal(false)}
        onAddFiles={addFilesToAlbum}
        userId={userId}
      />

      {/* Selection Controls */}
      {album.media_files.length > 0 && (
        <div className="bg-card border border-border rounded-lg p-4 mb-8 flex items-center justify-between">
          <div className="flex items-center gap-4">
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={selectedFiles.size === album.media_files.length && album.media_files.length > 0}
                onChange={handleSelectAll}
                className="w-5 h-5 rounded border-border accent-primary"
              />
              <span className="text-sm font-medium">
                {selectedFiles.size > 0 ? `${selectedFiles.size} selected` : "Select all"}
              </span>
            </label>
          </div>

          {selectedFiles.size > 0 && (
            <button
              onClick={handleRemoveFiles}
              className="inline-flex items-center gap-2 px-4 py-2 bg-destructive text-white rounded-lg hover:opacity-90 transition"
            >
              <Trash2 className="h-4 w-4" />
              Remove ({selectedFiles.size})
            </button>
          )}
        </div>
      )}
      {/* Photos Grid */}
      {album.media_files.length > 0 && (
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          {album.media_files.map((file) => (
            <div
              key={file.id}
              className={`relative group cursor-pointer rounded-lg overflow-hidden border-2 transition ${selectedFiles.has(file.id)
                  ? "border-primary bg-primary/10"
                  : "border-border hover:border-primary"
                }`}
              onClick={() => toggleFileSelection(file.id)}
            >
              {/* Image Thumbnail */}
              <img
                src={`${process.env.NEXT_PUBLIC_API_URL}/storage/${file.file_path}`}
                alt={file.name}
                className="w-full h-32 object-cover"
                onError={(e) => {
                  const img = e.target as HTMLImageElement
                  img.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect fill='%23f0f0f0' width='100' height='100'/%3E%3Ctext x='50' y='50' text-anchor='middle' dy='.3em' fill='%23999' font-size='14'%3EError%3C/text%3E%3C/svg%3E"
                }}
              />

              {/* Checkbox Overlay */}
              <div className="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                <div
                  className={`w-6 h-6 rounded border-2 flex items-center justify-center transition ${selectedFiles.has(file.id)
                      ? "bg-primary border-primary"
                      : "bg-white border-white"
                    }`}
                >
                  {selectedFiles.has(file.id) && (
                    <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                    </svg>
                  )}
                </div>
              </div>

              {/* File Info */}
              <div className="absolute bottom-0 left-0 right-0 bg-black/60 text-white p-2 text-xs opacity-0 group-hover:opacity-100 transition">
                <p className="truncate">{file.name}</p>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
