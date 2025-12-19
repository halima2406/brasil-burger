using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace BrasilBurger.Models
{
    [Table("users")]
    public class User
    {
        [Key]
        [Column("id")]
        public int Id { get; set; }

        [Column("nom")]
        public string Nom { get; set; } = "";

        [Column("prenom")]
        public string Prenom { get; set; } = "";

        [Column("email")]
        public string Email { get; set; } = "";

        [Column("telephone")]
        public string Telephone { get; set; } = "";

        [Column("mot_de_passe")]
        public string MotDePasse { get; set; } = "";

        [Column("type_user")]
        public string TypeUser { get; set; } = "CLIENT";
    }
}