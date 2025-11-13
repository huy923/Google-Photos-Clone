const apiBase = process.env.NEXT_PUBLIC_API_URL;
// console.log("API Base URL:", apiBase);

const API_BASE_URL = `${apiBase}/api`;
// console.log("Constructed API Base URL:", API_BASE_URL);
// Helper function to get auth headers
const getAuthHeaders = () => {
  const token = localStorage.getItem("token")
  return {
    "Content-Type": "application/json",
    ...(token && { Authorization: `Bearer ${token}` }),
  }
}

export const apiClient = {
  async get(endpoint: string) {
    const res = await fetch(`${API_BASE_URL}${endpoint}`, {
      headers: getAuthHeaders(),
    })
    if (!res.ok) throw new Error(`API error: ${res.status}`)
    // console.log("GET", endpoint, "response:", res);
    return res.json()
  },

  async post(endpoint: string, data: any) {
    const res = await fetch(`${API_BASE_URL}${endpoint}`, {
      method: "POST",
      headers: getAuthHeaders(),
      body: JSON.stringify(data),
    })
    if (!res.ok) throw new Error(`API error: ${res.status}`)
    return res.json()
  },

  async put(endpoint: string, data: any) {
    const res = await fetch(`${API_BASE_URL}${endpoint}`, {
      method: "PUT",
      headers: getAuthHeaders(),
      body: JSON.stringify(data),
    })
    if (!res.ok) throw new Error(`API error: ${res.status}`)
    return res.json()
  },

  async delete(endpoint: string) {
    const res = await fetch(`${API_BASE_URL}${endpoint}`, {
      method: "DELETE",
      headers: getAuthHeaders(),
    })
    if (!res.ok) throw new Error(`API error: ${res.status}`)
    return res.json()
  },

  async patch(endpoint: string, data: any) {
    const res = await fetch(`${API_BASE_URL}${endpoint}`, {
      method: "PATCH",
      headers: getAuthHeaders(),
      body: JSON.stringify(data),
    })
    if (!res.ok) throw new Error(`API error: ${res.status}`)
    return res.json()
  },

  // Authentication methods
  async login(email: string, password: string) {
    return this.post("/login", { email, password })
  },

  async register(data: { name: string; email: string; password: string; password_confirmation: string }) {
    return this.post("/register", data)
  },

  async logout() {
    return this.post("/logout", {})
  },

  async getMe() {
    return this.get("/me")
  },
}
