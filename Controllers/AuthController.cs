using Microsoft.AspNetCore.Mvc;
using BrasilBurger.Data;
using BrasilBurger.Models;
using BrasilBurger.Helpers;
using System.Linq;  
using Microsoft.AspNetCore.Http;

namespace BrasilBurger.Controllers
{
    public class AuthController : Controller
    {
        private ApplicationDbContext _context;

        public AuthController(ApplicationDbContext context)
        {
            _context = context;
        }

       
        public IActionResult Connexion()
        {
            return View();
        }

       
        [HttpPost]
        public IActionResult Connexion(string email, string motDePasse)
        {
          
            var users = _context.Users.ToList();
            User? user = null;
            
            foreach (var u in users)
            {
                if (u.Email == email && u.TypeUser == "CLIENT")
                {
                    user = u;
                    break;
                }
            }

            
            if (user == null)
            {
                ViewBag.Error = "Email ou mot de passe incorrect";
                return View();
            }

            if (user.MotDePasse != motDePasse)
            {
                ViewBag.Error = "Email ou mot de passe incorrect";
                return View();
            }

            HttpContext.Session.SetClientId(user.Id);
            HttpContext.Session.SetString("ClientNom", user.Prenom + " " + user.Nom);

            TempData["Success"] = "Connexion réussie !";
            return RedirectToAction("Index", "Home");
        }

       
        public IActionResult Inscription()
        {
            return View();
        }

       
        [HttpPost]
        public IActionResult Inscription(string nom, string prenom, string email, string telephone, string motDePasse)
        {
            
            var users = _context.Users.ToList();
            foreach (var u in users)
            {
                if (u.Email == email)
                {
                    ViewBag.Error = "Cet email est déjà utilisé";
                    return View();
                }
            }

           
            var user = new User();
            user.Nom = nom;
            user.Prenom = prenom;
            user.Email = email;
            user.Telephone = telephone;
            user.MotDePasse = motDePasse;
            user.TypeUser = "CLIENT";

           
            _context.Users.Add(user);
            _context.SaveChanges();

            TempData["Success"] = "Inscription réussie !";
            return RedirectToAction("Connexion");
        }

      
        public IActionResult Deconnexion()
        {
            HttpContext.Session.Clear();
            TempData["Success"] = "Vous êtes déconnecté";
            return RedirectToAction("Index", "Home");
        }
    }
}