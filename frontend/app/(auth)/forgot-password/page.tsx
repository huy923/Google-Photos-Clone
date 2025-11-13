"use client"
import type React from "react"
import { useState } from "react"
import { useRouter } from "next/navigation"
import { apiClient } from "@/lib/api-client"
import { Button } from "@/components/ui/button"
import { User } from "@/app/types"
import { Mail} from "lucide-react"

export default function LoginPage() {
  const router = useRouter()
  const [email, setEmail] = useState("")
  const [error, setError] = useState("")
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError("")
    setLoading(true)

    try {
      const response: { user: User; token: string } = await apiClient.post("/fogot-password", { email})

      // Store user data and token
      localStorage.setItem("user", JSON.stringify(response.user))
      localStorage.setItem("token", response.token)
      localStorage.setItem("userId", response.user.id.toString())

      router.push("/")
    } catch (err: any) {
      setError(err.message || "Đăng nhập thất bại")
    } finally {
      setLoading(false)
    }
  }
 
  return (
    <div className="min-h-screen bg-linear-to-br from-background to-card flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        <div className="bg-card border border-border rounded-2xl shadow-xl p-8 space-y-6">
          <div className="text-center">
            <h1 className="text-3xl font-bold mb-2">Enter your email!</h1>
          </div>

          {error && (
            <div className="bg-destructive/10 border border-destructive/20 text-destructive p-3 rounded-lg text-sm flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
              </svg>
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="relative">
              <Mail className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-muted-foreground" />
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="your@email.com"
                required
              />
            </div>
            
            <Button
              type="submit"
              disabled={loading}
              className="w-full bg-primary text-primary-foreground py-3 rounded-lg font-semibold text-base transition hover:bg-primary/90 disabled:opacity-50"
            >
              {loading ? "Signing in..." : "Sign In"}
            </Button>
          </form>
        </div>
      </div>
    </div>
  )
}
