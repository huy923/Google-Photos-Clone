"use client"
import type React from "react"
import { Geist } from "next/font/google"
import "./globals.css"
import { ThemeWrapper } from "@/components/theme-wrapper"
import Header from "@/components/header"
import { usePathname } from "next/navigation"
import { ThemeToggle } from "@/components/theme-toggle"

const geistSans = Geist({ subsets: ["latin"] })

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  const pathname = usePathname()
  const isAuthPage = pathname.startsWith('/login') || pathname.startsWith('/signup') || pathname.startsWith('/forgot-password');

  return (
    <html lang="vi" suppressHydrationWarning>
      <body className={`${geistSans.className} bg-background text-foreground`}>
        <ThemeWrapper>
          {!isAuthPage && <Header />}
          {isAuthPage &&
            <div className="grid justify-end mt-0.5">
              <ThemeToggle />
            </div>
          }
          {children}
        </ThemeWrapper>
      </body>
    </html>
  )
}

