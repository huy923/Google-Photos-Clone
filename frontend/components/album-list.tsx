"use client"

import type React from "react"

import { useState, useEffect } from "react"
import Link from "next/link"
import { apiClient } from "@/lib/api-client"
import { Button } from "./ui/button"
import { Trash2, Plus, X } from "lucide-react"

interface Album {
  id: number
  name: string
  description: string
  type: string
  media_count?: number
}

export function AlbumList({ userId }: { userId: string }) {
  const [albums, setAlbums] = useState<Album[]>([])
  const [loading, setLoading] = useState(true)
  const [newAlbumName, setNewAlbumName] = useState("")
  const [newAlbumDescription, setNewAlbumDescription] = useState("")
  const [showForm, setShowForm] = useState(false)
  const [isCreating, setIsCreating] = useState(false)

  useEffect(() => {
    fetchAlbums()
  }, [userId])

  const fetchAlbums = async () => {
    try {
      const data = await apiClient.get(`/albums?user_id=${userId}`)
      setAlbums(Array.isArray(data) ? data : data.data || [])
    } catch (error) {
      console.error("Failed to fetch albums:", error)
    } finally {
      setLoading(false)
    }
  }

  const handleCreateAlbum = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!newAlbumName.trim()) return

    setIsCreating(true)
    try {
      const newAlbum = await apiClient.post("/albums", {
        user_id: userId,
        name: newAlbumName,
        description: newAlbumDescription,
        type: "manual",
      })
      setAlbums([newAlbum, ...albums])
      setNewAlbumName("")
      setNewAlbumDescription("")
      setShowForm(false)
      alert("Album created successfully!")
    } catch (error) {
      console.error("Failed to create album:", error)
      alert("Failed to create album")
    } finally {
      setIsCreating(false)
    }
  }

  const handleDeleteAlbum = async (id: number) => {
    if (!confirm("Are you sure you want to delete this album?")) return

    try {
      await apiClient.delete(`/albums/${id}`)
      setAlbums(albums.filter((a) => a.id !== id))
      alert("Album deleted successfully!")
    } catch (error) {
      console.error("Failed to delete album:", error)
      alert("Failed to delete album")
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
    <div>
      {/* Create Album Form */}
      {showForm && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-card rounded-lg p-6 max-w-md w-full border border-border shadow-lg">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-2xl font-bold">Create New Album</h2>
              <button
                onClick={() => setShowForm(false)}
                className="p-2 hover:bg-accent rounded-lg transition"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            <form onSubmit={handleCreateAlbum} className="space-y-4">
              <div>
                <label className="block text-sm font-medium mb-2">Album Name *</label>
                <input
                  type="text"
                  value={newAlbumName}
                  onChange={(e) => setNewAlbumName(e.target.value)}
                  placeholder="Enter album name"
                  className="w-full px-4 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
                  autoFocus
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium mb-2">Description</label>
                <textarea
                  value={newAlbumDescription}
                  onChange={(e) => setNewAlbumDescription(e.target.value)}
                  placeholder="Enter album description (optional)"
                  rows={3}
                  className="w-full px-4 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
                />
              </div>

              <div className="flex gap-2 pt-4">
                <button
                  type="submit"
                  disabled={isCreating}
                  className="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:opacity-90 transition disabled:opacity-50"
                >
                  {isCreating ? "Creating..." : "Create Album"}
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setShowForm(false)
                    setNewAlbumName("")
                    setNewAlbumDescription("")
                  }}
                  className="flex-1 px-4 py-2 border border-border rounded-lg hover:bg-accent transition"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Create Album Button */}
      {!showForm && (
        <div className="mb-8">
          <button
            onClick={() => setShowForm(true)}
            className="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:opacity-90 transition font-medium"
          >
            <Plus className="h-5 w-5" />
            Create New Album
          </button>
        </div>
      )}

      {/* Albums Grid */}
      {albums.length === 0 ? (
        <div className="text-center py-12 bg-card border border-border rounded-lg">
          <p className="text-lg text-muted-foreground mb-4">📚 No albums yet</p>
          <p className="text-sm text-muted-foreground mb-6">Create your first album to organize your photos</p>
          <button
            onClick={() => setShowForm(true)}
            className="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:opacity-90 transition"
          >
            <Plus className="h-4 w-4" />
            Create Album
          </button>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
          {albums.map((album) => (
            <Link
              key={album.id}
              href={`/albums/${album.id}`}
              className="group bg-card border border-border rounded-lg overflow-hidden hover:border-primary hover:shadow-lg transition"
            >
              {/* Album Header */}
              <div className="bg-primary/10 p-6 border-b border-border">
                <h3 className="text-xl font-bold group-hover:text-primary transition mb-1">
                  📁 {album.name}
                </h3>
                <p className="text-sm text-muted-foreground">
                  {album.media_count || 0} photos
                </p>
              </div>

              {/* Album Body */}
              <div className="p-4">
                <p className="text-sm text-muted-foreground mb-4 line-clamp-2">
                  {album.description || "No description"}
                </p>

                {/* Delete Button */}
                <Button
                  onClick={(e) => {
                    e.preventDefault()
                    handleDeleteAlbum(album.id)
                  }}
                  variant="destructive"
                  size="sm"
                  className=" flex items-center justify-center gap-2"
                >
                  <Trash2 className="h-4 w-4" />
                  Delete Album
                </Button>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  )
}
