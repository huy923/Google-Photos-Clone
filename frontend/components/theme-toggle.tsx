"use client"

import { useTheme } from "@/lib/theme-provider"
import { Moon, Sun } from "lucide-react"
import { Button } from "./ui/button"
export function ThemeToggle() {
  const { theme, toggleTheme, mounted } = useTheme()

  if (!mounted) return null

  return (
    <Button
      onClick={toggleTheme}
      variant={"outline"}
      size={"icon"}
      aria-label="Toggle theme"
    >
      {theme === "light" ? <Moon className="w-5 h-5 " /> : <Sun className="w-5 h-5" />}
    </Button>
  )
}