"use client"

import type React from "react"
import { ThemeProvider } from "@/lib/theme-provider"

export function ThemeWrapper({ children }: { children: React.ReactNode }) {
  return <ThemeProvider>{children}</ThemeProvider>
}
